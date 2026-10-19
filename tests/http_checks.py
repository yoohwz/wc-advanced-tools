"""Real HTTP checks against the synthetic disposable site started by run-runtime.sh."""
import csv
import http.cookiejar
import io
import re
import time
import urllib.error
import urllib.parse
import urllib.request

BASE = "http://127.0.0.1:8093"


def request(client, path, data=None):
    payload = None if data is None else urllib.parse.urlencode(data).encode()
    try:
        response = client.open(BASE + path, payload, timeout=30)
    except urllib.error.HTTPError as error:
        response = error
    return response.status, response.headers, response.read()


def client_for(login=None):
    client = urllib.request.build_opener(
        urllib.request.HTTPCookieProcessor(http.cookiejar.CookieJar())
    )
    if login:
        request(client, "/wp-login.php")
        status, _, _ = request(client, "/wp-login.php", {
            "log": login, "pwd": "at3-disposable-only",
            "wp-submit": "Log In", "testcookie": "1",
            "redirect_to": BASE + "/wp-admin/",
        })
        assert status == 200, f"Login failed: {status}"
    return client


for attempt in range(30):
    try:
        request(client_for(), "/wp-login.php")
        break
    except urllib.error.URLError:
        time.sleep(0.2)
else:
    raise RuntimeError("Disposable HTTP server did not start")

manager = client_for("at3manager")
status, _, html = request(manager, "/wp-admin/admin.php?page=wc-advanced-tools")
assert status == 200 and b"Quick Order Export" in html and b"Download CSV" in html
nonce = re.search(rb'name="_wpnonce" value="([^"]+)"', html).group(1).decode()
form = {
    "action": "wc_advanced_tools_export", "_wpnonce": nonce,
    "start_date": "2020-03-08", "end_date": "2020-03-08", "status": "wc-completed",
}
path = "/wp-admin/admin-post.php"
status, headers, body = request(manager, path, form)
assert status == 200, (status, body[:200])
assert headers.get_content_type() == "text/csv"
assert headers["Content-Disposition"] == 'attachment; filename="wc-advanced-tools-orders.csv"'
assert "no-store" in headers["Cache-Control"] and "private" in headers["Cache-Control"]
assert headers["X-Content-Type-Options"] == "nosniff"
assert int(headers["Content-Length"]) == len(body)
rows = list(csv.reader(io.StringIO(body.decode("utf-8"))))
assert len(rows) == 3 and all(len(row) == 6 for row in rows)
assert rows[1][2:] == ["2020-03-08T00:00:00-05:00", "completed", "USD", "12.30"]
assert b"private-fixture" not in body and b"Private fixture address" not in body
status, headers, body = request(manager, path, dict(form, start_date="2021-01-01", end_date="2021-01-01", status="wc-pending"))
assert status == 200 and headers.get_content_type() == "text/csv"
assert len(list(csv.reader(io.StringIO(body.decode())))) == 1001, "1,000-order HTTP export was truncated"

for client, data, expected in [
    (client_for(), form, 403),
    (client_for("at3subscriber"), form, 403),
    (manager, dict(form, _wpnonce="bad"), 403),
    (manager, dict(form, start_date="2020-02-30"), 400),
    (manager, dict(form, status="unknown"), 400),
    (manager, dict(form, start_date="2021-01-01", end_date="2021-01-01", status="all"), 400),
]:
    status, headers, body = request(client, path, data)
    assert status == expected, (status, expected)
    assert headers.get_content_type() != "text/csv"
    assert "Content-Disposition" not in headers
    assert "no-store" in headers["Cache-Control"]

status, headers, body = request(manager, path, dict(form, start_date="2010-01-01", end_date="2010-01-01"))
assert status == 200 and len(list(csv.reader(io.StringIO(body.decode())))) == 1
print("HTTP: manager form/download, headers, exact CSV, empty, oversized, anonymous/subscriber/nonce/filter denial passed.")
