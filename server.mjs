import { createServer } from 'node:http';
import { readFile } from 'node:fs/promises';
import { extname, resolve, sep } from 'node:path';
import { fileURLToPath } from 'node:url';

const root = resolve(fileURLToPath(new URL('./node-public/', import.meta.url)));
const port = Number(process.env.PORT || 3000);
const types = { '.html': 'text/html; charset=utf-8', '.css': 'text/css; charset=utf-8', '.js': 'text/javascript; charset=utf-8', '.svg': 'image/svg+xml' };

createServer(async (request, response) => {
  const pathname = new URL(request.url, `http://${request.headers.host}`).pathname;
  const json = (status, body) => { response.writeHead(status, { 'content-type': 'application/json; charset=utf-8', 'cache-control': 'no-store' }); response.end(JSON.stringify(body)); };
  if (pathname === '/health') {
    json(200, { status: 'ok', runtime: 'node', database: 'supabase' });
    return;
  }
  if (pathname === '/api/config') { json(200, { supabaseUrl: process.env.SUPABASE_URL || '', supabaseAnonKey: process.env.SUPABASE_ANON_KEY || '' }); return; }
  if (pathname.startsWith('/api/admin/users')) {
    const base = (process.env.SUPABASE_URL || '').replace(/\/$/, '');
    const anon = process.env.SUPABASE_ANON_KEY || '';
    const secret = process.env.SUPABASE_SERVICE_ROLE_KEY || '';
    const token = request.headers.authorization?.replace(/^Bearer\s+/i, '');
    if (!base || !anon || !secret || !token) { json(503, { error: 'Konfigurasi admin Supabase belum lengkap.' }); return; }
    try {
      const userResponse = await fetch(`${base}/auth/v1/user`, { headers: { apikey: anon, authorization: `Bearer ${token}` } });
      if (!userResponse.ok) { json(401, { error: 'Sesi tidak valid.' }); return; }
      const user = await userResponse.json();
      const adminResponse = await fetch(`${base}/rest/v1/profiles?id=eq.${encodeURIComponent(user.id)}&select=role,is_active`, { headers: { apikey: secret, authorization: `Bearer ${secret}` } });
      const [profile] = await adminResponse.json();
      if (!adminResponse.ok || profile?.role !== 'admin' || profile?.is_active !== true) { json(403, { error: 'Akses admin diperlukan.' }); return; }
      const adminHeaders = { apikey: secret, authorization: `Bearer ${secret}`, 'content-type': 'application/json' };
      if (request.method === 'GET') {
        const result = await fetch(`${base}/rest/v1/profiles?select=id,name,email,role,is_active,created_at&order=name.asc`, { headers: { apikey: secret, authorization: `Bearer ${secret}` } });
        json(result.status, await result.json()); return;
      }
      const chunks = []; for await (const chunk of request) chunks.push(chunk);
      const body = JSON.parse(Buffer.concat(chunks).toString() || '{}');
      const userId = new URL(request.url, `http://${request.headers.host}`).searchParams.get('id');
      if (request.method === 'POST') {
        if (!body.name || !body.email || !body.password || !['admin','kasir'].includes(body.role)) { json(422, { error: 'Nama, email, kata sandi, dan peran wajib diisi.' }); return; }
        const created = await fetch(`${base}/auth/v1/admin/users`, { method: 'POST', headers: adminHeaders, body: JSON.stringify({ email: body.email.toLowerCase(), password: body.password, email_confirm: true, app_metadata: { role: body.role }, user_metadata: { name: body.name } }) });
        const createdUser = await created.json();
        if (!created.ok) { json(created.status, { error: createdUser.msg || createdUser.message || 'Akun tidak dapat dibuat.' }); return; }
        await fetch(`${base}/rest/v1/profiles?id=eq.${createdUser.id}`, { method: 'PATCH', headers: { ...adminHeaders, prefer: 'return=minimal' }, body: JSON.stringify({ name: body.name, email: body.email.toLowerCase(), role: body.role, is_active: body.is_active !== false }) });
        json(201, { id: createdUser.id, name: body.name, email: body.email.toLowerCase(), role: body.role }); return;
      }
      if (request.method === 'PUT' && userId) {
        if (!body.name || !body.email || !['admin','kasir'].includes(body.role)) { json(422, { error: 'Nama, email, dan peran wajib diisi.' }); return; }
        if (userId === user.id && (body.role !== 'admin' || body.is_active === false)) { json(422, { error: 'Anda tidak dapat menonaktifkan atau menurunkan peran akun sendiri.' }); return; }
        const countResponse = await fetch(`${base}/rest/v1/profiles?role=eq.admin&is_active=eq.true&select=id`, { headers: { apikey: secret, authorization: `Bearer ${secret}` } });
        const activeAdmins = await countResponse.json();
        const targetResponse = await fetch(`${base}/rest/v1/profiles?id=eq.${encodeURIComponent(userId)}&select=role,is_active`, { headers: { apikey: secret, authorization: `Bearer ${secret}` } });
        const [target] = await targetResponse.json();
        if (!target) { json(404, { error: 'Pengguna tidak ditemukan.' }); return; }
        if (target.role === 'admin' && target.is_active && (body.role !== 'admin' || body.is_active === false) && activeAdmins.length <= 1) { json(422, { error: 'Admin aktif terakhir tidak dapat dinonaktifkan atau diturunkan perannya.' }); return; }
        const updated = await fetch(`${base}/auth/v1/admin/users/${encodeURIComponent(userId)}`, { method: 'PUT', headers: adminHeaders, body: JSON.stringify({ email: body.email.toLowerCase(), app_metadata: { role: body.role }, user_metadata: { name: body.name }, ...(body.password ? { password: body.password } : {}) }) });
        if (!updated.ok) { const err = await updated.json(); json(updated.status, { error: err.msg || err.message || 'Akun tidak dapat diperbarui.' }); return; }
        await fetch(`${base}/rest/v1/profiles?id=eq.${encodeURIComponent(userId)}`, { method: 'PATCH', headers: { ...adminHeaders, prefer: 'return=minimal' }, body: JSON.stringify({ name: body.name, email: body.email.toLowerCase(), role: body.role, is_active: body.is_active !== false, updated_at: new Date().toISOString() }) });
        json(200, { success: true }); return;
      }
      json(405, { error: 'Metode tidak didukung.' });
    } catch (error) { json(500, { error: error.message || 'Terjadi kesalahan server.' }); }
    return;
  }
  const requested = pathname === '/' ? 'index.html' : decodeURIComponent(pathname.slice(1));
  const file = resolve(root, requested);
  if (file !== root && !file.startsWith(root + sep)) {
    response.writeHead(403).end('Forbidden');
    return;
  }
  try {
    const body = await readFile(file);
    response.writeHead(200, { 'content-type': types[extname(file)] || 'application/octet-stream', 'x-content-type-options': 'nosniff' });
    response.end(body);
  } catch {
    response.writeHead(404, { 'content-type': 'text/plain; charset=utf-8' }).end('Not found');
  }
}).listen(port, () => console.log(`WarungCerdas Node server listening on http://localhost:${port}`));
