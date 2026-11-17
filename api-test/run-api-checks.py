from pathlib import Path
import json, urllib.error, urllib.parse, urllib.request, time, html, os, datetime
root=Path(__file__).resolve().parent.parent
evidence=root/'evidence'; evidence.mkdir(parents=True,exist_ok=True)
base=os.environ.get('API_BASE_URL', 'http://127.0.0.1:8080')
records=[]
def request(name, method, path, body=None, expected=None):
    url=base+path
    payload=None if body is None else json.dumps(body, ensure_ascii=False).encode()
    headers={'Accept':'application/json'}
    if payload is not None: headers['Content-Type']='application/json'
    req=urllib.request.Request(url, data=payload, headers=headers, method=method)
    started=time.time()
    try:
        with urllib.request.urlopen(req, timeout=8) as resp:
            status=resp.status; raw=resp.read().decode()
    except urllib.error.HTTPError as err:
        status=err.code; raw=err.read().decode()
    elapsed=round((time.time()-started)*1000)
    try: response=json.loads(raw)
    except Exception: response={'raw':raw}
    entry={'name':name,'method':method,'url':url,'path':path,'request':body,'status':status,'expected_status':expected,'duration_ms':elapsed,'response':response}
    records.append(entry)
    if status != expected:
        raise SystemExit(f'{name}: expected HTTP {expected}, got {status}: {raw}')
    return response

request('GET daftar data','GET','/api/umkm?page=1&per_page=10',expected=200)
created=request('POST tambah data','POST','/api/umkm',{
    'nama_umkm':'Warung Banua','pemilik':'Andi Dermawan','alamat':'Jl. Veteran, Banjarmasin',
    'kategori':'Kuliner','kontak':'081234567890'
},201)
created_id=created['data']['id']
request('GET detail data','GET',f'/api/umkm/{created_id}',expected=200)
request('PUT ubah data','PUT',f'/api/umkm/{created_id}',{
    'nama_umkm':'Warung Banua Baru','pemilik':'Andi Dermawan','alamat':'Jl. Veteran, Banjarmasin',
    'kategori':'Kuliner','kontak':'081234567891'
},200)
request('POST validasi gagal','POST','/api/umkm',{},422)
request('GET id tidak ditemukan','GET','/api/umkm/999999',expected=404)
request('DELETE hapus data','DELETE',f'/api/umkm/{created_id}',expected=200)
request('GET daftar setelah uji','GET','/api/umkm?page=1&per_page=10',expected=200)
(evidence/'api-test-results.json').write_text(json.dumps(records,ensure_ascii=False,indent=2)+'\n',encoding='utf-8')

cards=[]
for entry in records:
    req_body='' if entry['request'] is None else json.dumps(entry['request'],ensure_ascii=False,indent=2)
    response=json.dumps(entry['response'],ensure_ascii=False,indent=2)
    badge='pass' if entry['status']==entry['expected_status'] else 'fail'
    request_html=f'<pre>{html.escape(req_body)}</pre>' if req_body else '<div class="muted">Tanpa request body</div>'
    cards.append(f'''<article class="card"><div class="card-head"><div><span class="method {html.escape(entry['method'].lower())}">{html.escape(entry['method'])}</span><strong>{html.escape(entry['name'])}</strong></div><span class="status {badge}">HTTP {entry['status']} · sesuai</span></div><div class="url">{html.escape(entry['path'])}</div><div class="label">REQUEST BODY</div>{request_html}<div class="label">RESPONSE · {entry['duration_ms']} ms</div><pre>{html.escape(response)}</pre></article>''')
html_doc='''<!doctype html><html lang="id"><head><meta charset="utf-8"><title>API Test Capture - UMKM Lokal</title><style>
*{box-sizing:border-box}body{margin:0;background:#eef3f0;color:#18251e;font:14px/1.45 Arial,sans-serif;padding:34px}.page{max-width:1480px;margin:auto}.hero{background:linear-gradient(120deg,#115e49,#238365);color:white;border-radius:22px;padding:26px 30px;margin-bottom:18px;display:flex;justify-content:space-between;align-items:center}.eyebrow{text-transform:uppercase;letter-spacing:1.5px;font-weight:700;font-size:11px;color:#c5ead7}.hero h1{font-size:30px;margin:5px 0}.hero p{margin:0;color:#d7eee1}.summary{background:#fff;border-radius:14px;padding:12px 16px;color:#17664f;font-weight:700;text-align:center;min-width:130px}.grid{display:grid;grid-template-columns:1fr 1fr;gap:14px}.card{background:white;border:1px solid #dfe8e2;border-radius:16px;padding:16px;box-shadow:0 2px 7px #1d3b2b0c;break-inside:avoid}.card-head{display:flex;align-items:center;justify-content:space-between;gap:8px}.card-head>div{display:flex;align-items:center;gap:9px}.card-head strong{font-size:16px}.method{font-weight:800;border-radius:7px;padding:4px 7px;font-size:11px;color:white}.get{background:#2672c9}.post{background:#198553}.put{background:#ad7315}.delete{background:#bd4e4e}.status{font-size:11px;font-weight:700;border-radius:20px;padding:5px 9px;white-space:nowrap}.pass{background:#e5f5eb;color:#176c3b}.fail{background:#ffe5e3;color:#9b2b21}.url{font-family:monospace;color:#41594d;font-size:12px;margin:8px 0 10px}.label{font-size:10px;letter-spacing:1px;color:#74847b;font-weight:800;margin:10px 0 4px}pre{white-space:pre-wrap;overflow-wrap:anywhere;margin:0;background:#f6f8f7;border-radius:9px;padding:10px 11px;color:#24352c;font:11px/1.35 'DejaVu Sans Mono',monospace}.muted{font-size:11px;color:#718078;background:#f6f8f7;padding:8px 11px;border-radius:9px}.footer{font-size:11px;color:#66756d;text-align:right;margin:12px 4px 0}
</style></head><body><main class="page"><header class="hero"><div><div class="eyebrow">Capture Pengujian API · CI4</div><h1>UMKM Lokal REST API</h1><p>Request HTTP langsung ke server CodeIgniter 4 lokal · DATE_HERE</p></div><div class="summary">8 / 8<br>BERHASIL</div></header><section class="grid">'''+''.join(cards)+'''</section><div class="footer">Status HTTP dan isi JSON diambil dari respons server saat pengujian.</div></main></body></html>'''
html_doc=html_doc.replace('DATE_HERE', datetime.datetime.now().astimezone().strftime('%d %B %Y'))
(evidence/'api-test-capture.html').write_text(html_doc,encoding='utf-8')
print(json.dumps([{'test':r['name'],'status':r['status'],'expected':r['expected_status']} for r in records],ensure_ascii=False,indent=2))
