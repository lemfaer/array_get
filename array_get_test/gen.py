import os
import json
import gzip
import base64

curdir = os.path.dirname(os.path.realpath(__file__))
outpath1 = os.path.join(curdir, "test.json")
outpath2 = os.path.join(curdir, "test2.json")
outpath3 = os.path.join(curdir, "test.gz")
outpath4 = os.path.join(curdir, "test.txt")

top = 26
data = []

a = [
  -11, 31, -93, 28, -24,
  67, -72, -50, 69, 60,
  -20, -38, 53, 100, -98,
  90, -27, 75, -26, 98,
  -28, -63, 68, -22, -49
]

for sa in list(range(-top, top+1)) + [None]:
  for so in list(range(-top, top+1)) + [None]:
    for se in list(range(-top, top+1)) + [None]:
      if se != 0:
        data.append([
          (sa, so, se),
          a[sa:so:se]
        ])

with open(outpath1, "wb") as f1:
  json_str = json.dumps(data, indent=None, separators=(",", ":"))
  json_bytes = json_str.encode('utf-8')
  f1.write(json_bytes)

with open(outpath2, "wb") as f2:
  json_str = json.dumps(data, indent="\t", separators=(",", ":"))
  json_bytes = json_str.encode('utf-8')
  f2.write(json_bytes)

with open(outpath3, "wb") as ft:
  with gzip.GzipFile(fileobj=ft, mode="w") as f3:
    json_str = json.dumps(data, indent=None, separators=(",", ":"))
    json_bytes = json_str.encode('utf-8')
    f3.write(json_bytes)

with open(outpath3, "rb") as f3:
  with open(outpath4, "wb") as f4:
    f4.write(base64.b64encode(f3.read()))
