# -*- coding: utf-8 -*-
import json
import sys

paths = [
    r"C:\Users\Meu PC\.cursor\projects\c-xampp-htdocs-pjt-wordspace-cti-code-workspace\agent-transcripts\6b4d0525-bc6d-40aa-ae67-c8e9b77cc5b4\6b4d0525-bc6d-40aa-ae67-c8e9b77cc5b4.jsonl",
    r"C:\Users\Meu PC\.cursor\projects\c-xampp-htdocs-pjt-painel-cti\agent-transcripts\6b4d0525-bc6d-40aa-ae67-c8e9b77cc5b4\6b4d0525-bc6d-40aa-ae67-c8e9b77cc5b4.jsonl",
]
out = r"C:\xampp\htdocs\pjt\painel-cti\database\lms_conquistas_v3.sql"
found = None

for path in paths:
    try:
        f = open(path, "r", encoding="utf-8")
    except FileNotFoundError:
        continue
    with f:
        for line in f:
            if "lms_conquistas_v3.sql" not in line:
                continue
            if "INSERT INTO lms_conquistas_def" not in line:
                continue
            obj = json.loads(line)
            for part in obj.get("message", {}).get("content", []):
                if part.get("type") == "tool_use" and part.get("name") == "Write":
                    inp = part.get("input", {})
                    if "lms_conquistas_v3.sql" in str(inp.get("path", "")):
                        cand = inp.get("contents") or ""
                        if "INSERT INTO lms_conquistas_def" in cand and len(cand) > len(found or ""):
                            found = cand
                if part.get("type") == "text":
                    t = part.get("text") or ""
                    if "```sql" in t and "INSERT INTO lms_conquistas_def" in t:
                        start = t.find("```sql") + 6
                        end = t.find("```", start)
                        cand = t[start:end].strip()
                        if len(cand) > len(found or ""):
                            found = cand

if not found:
    print("NOT FOUND")
    sys.exit(1)

with open(out, "w", encoding="utf-8", newline="\n") as w:
    w.write(found if found.endswith("\n") else found + "\n")
print("OK", len(found), "chars")
print("INSERT blocks", found.count("INSERT INTO"))
