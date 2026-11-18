// لایه‌ی داده‌ی سایت: همان واسطه‌ای که app.js انتظارش را دارد (window.claude.use)، ولی به‌جای دیتابیس Claude
// با api/api.php روی Strato کار می‌کند. هر ۲۰ ثانیه داده‌ها تازه می‌شوند و بعد از هر ذخیره فوراً به‌روز می‌شوند.
(function () {
  var API = 'api/api.php';
  var POLL_MS = 20000;

  // شناسه‌ی ثابت مرورگر برای ثبت معرفی برنامه (قانون: هر کس فقط submissions/خودش را می‌نویسد)
  var uid = localStorage.getItem('bb_uid');
  if (!uid) {
    uid = 'u_' + Math.random().toString(36).slice(2, 10) + Date.now().toString(36);
    localStorage.setItem('bb_uid', uid);
  }

  async function call(op, params, body) {
    var qs = new URLSearchParams(Object.assign({ op: op }, params || {})).toString();
    var opts = { credentials: 'same-origin', method: body ? 'POST' : 'GET' };
    if (body) { opts.headers = { 'Content-Type': 'application/json' }; opts.body = JSON.stringify(body); }
    var r = await fetch(API + '?' + qs, opts);
    var j = await r.json().catch(function () { return {}; });
    if (!r.ok) {
      var e = new Error(j.error || ('HTTP ' + r.status));
      e.code = r.status === 403 ? 'invalid_argument' : 'error';
      throw e;
    }
    return j;
  }

  var listeners = [];
  function refreshAll() { listeners.forEach(function (fn) { fn(); }); }

  function subscribe(fetcher, onData, onErr) {
    var stopped = false;
    async function tick() {
      if (stopped) return;
      try { onData(await fetcher()); } catch (e) { if (onErr) onErr(e); }
    }
    var fn = tick;
    listeners.push(fn);
    tick();
    var timer = setInterval(tick, POLL_MS);
    return function () { stopped = true; clearInterval(timer); listeners = listeners.filter(function (x) { return x !== fn; }); };
  }

  function makeDoc(snapshotData) {
    return { exists: snapshotData !== null, data: function () { return snapshotData || {}; } };
  }

  function docRef(path) {
    return {
      path: path,
      async get() {
        var j = await call('get', { path: path, uid: uid });
        return makeDoc(j.exists ? j.data : null);
      },
      async set(data) { await call('write', {}, { path: path, mode: 'set', data: data, uid: uid }); refreshAll(); },
      async merge(data) { await call('write', {}, { path: path, mode: 'merge', data: data, uid: uid }); refreshAll(); },
      async update(data) { await call('write', {}, { path: path, mode: 'update', data: data, uid: uid }); refreshAll(); },
      onSnapshot(cb, errCb) {
        return subscribe(async function () {
          var j = await call('get', { path: path, uid: uid });
          return makeDoc(j.exists ? j.data : null);
        }, cb, errCb);
      }
    };
  }

  function collRef(name) {
    return {
      name: name,
      onSnapshot(cb, errCb) {
        return subscribe(async function () {
          var j = await call('list', { c: name, uid: uid });
          return {
            docs: (j.docs || []).map(function (d) {
              return { id: d.id, data: function () { return d.data || {}; } };
            })
          };
        }, cb, errCb);
      }
    };
  }

  var db = {
    collection: function (name) { return collRef(name); },
    doc: function (path) { return docRef(path); }
  };

  var adminCache = null;
  async function isAdmin() {
    try { adminCache = await call('whoami'); } catch (e) { adminCache = { admin: false }; }
    return !!adminCache.admin;
  }

  var user = {
    id: async function () { return uid; },
    canEdit: async function () { return isAdmin(); },
    can: async function () { return null; }
  };

  window.claude = {
    use: async function (name) {
      if (name === 'db') return db;
      if (name === 'user') return user;
      return null;
    }
  };
})();
