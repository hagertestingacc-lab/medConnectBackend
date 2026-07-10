import re, os, json
root = os.getcwd()
migrations_dir = os.path.join(root, 'database', 'migrations')
create_re = re.compile(r"Schema::create\(\s*['\"]([\w_]+)['\"]\s*,\s*function\s*\(.*?\)\s*\{", re.S)
foreign_re1 = re.compile(r"->foreignId\(\s*['\"]([\w_]+)['\"]\s*\)(?:->constrained\((?:['\"]([\w_]+)['\"])?(?:\s*,\s*['\"]([\w_]+)['\"])??\))?")
foreign_re2 = re.compile(r"->foreign\(\s*['\"]([\w_]+)['\"]\s*\)->references\(\s*['\"]([\w_]+)['\"]\s*\)->on\(\s*['\"]([\w_]+)['\"]\s*\)")
field_type_re = re.compile(r"\$table->([a-zA-Z_]+)\(([^)]*)\)")

tables = {}
for fname in sorted(os.listdir(migrations_dir)):
    if not fname.endswith('.php'):
        continue
    path = os.path.join(migrations_dir, fname)
    text = open(path, encoding='utf-8', errors='ignore').read()
    for m in create_re.finditer(text):
        tbl = m.group(1)
        start = m.end()
        body = ''
        depth = 1
        i = start
        while i < len(text) and depth > 0:
            if text[i] == '{':
                depth += 1
            elif text[i] == '}':
                depth -= 1
            if depth > 0:
                body += text[i]
            i += 1
        if tbl not in tables:
            tables[tbl] = {'columns': {}, 'fks': [], 'uniques': [], 'pk': [], 'comment': fname}
        lines = [line.strip() for line in body.splitlines() if line.strip() and not line.strip().startswith('//')]
        for line in lines:
            if '->id()' in line:
                tables[tbl]['columns']['id'] = {'type': 'id', 'nullable': False, 'default': None, 'pk': True, 'unique': False}
                tables[tbl]['pk'].append('id')
            if '->timestamps()' in line:
                for col in ['created_at', 'updated_at']:
                    tables[tbl]['columns'][col] = {'type': 'timestamp', 'nullable': False, 'default': None, 'pk': False, 'unique': False}
            if '->softDeletes()' in line:
                tables[tbl]['columns']['deleted_at'] = {'type': 'timestamp', 'nullable': True, 'default': None, 'pk': False, 'unique': False}
            if '->' in line and 'Schema::' not in line and 'function' not in line:
                for fm in field_type_re.finditer(line):
                    typ = fm.group(1)
                    colraw = fm.group(2).strip()
                    if not colraw:
                        continue
                    col = None
                    if colraw.startswith(('"', "'")):
                        col = colraw.strip()[1:-1]
                    elif ',' in colraw:
                        first = colraw.split(',')[0].strip()
                        if first.startswith(('"', "'")):
                            col = first[1:-1]
                    if not col:
                        continue
                    if typ in ('foreignId', 'foreignIdFor'):
                        nullable = 'nullable()' in line
                        unique = 'unique()' in line
                        default = None
                        tables[tbl]['columns'][col] = {'type': 'foreignId', 'nullable': nullable, 'default': default, 'pk': False, 'unique': unique}
                        m2 = re.search(r"->constrained\(\s*['\"]?([\w_]+)?['\"]?\s*\)(?:->onDelete\('([^']+)'\))?(?:->onUpdate\('([^']+)'\))?", line)
                        if m2:
                            ref_tbl = m2.group(1) if m2.group(1) else (col[:-3] if col.endswith('_id') else None)
                            ref_col = 'id'
                            if ref_tbl:
                                tables[tbl]['fks'].append({'column': col, 'references': ref_col, 'on': ref_tbl})
                        continue
                    if typ == 'foreign':
                        continue
                    if typ == 'primary':
                        continue
                    if col in tables[tbl]['columns'] and tables[tbl]['columns'][col]['type'] == 'foreignId':
                        continue
                    nullable = 'nullable()' in line
                    unique = 'unique()' in line
                    default = None
                    mdef = re.search(r"->default\(([^)]+)\)", line)
                    if mdef:
                        default = mdef.group(1).strip()
                    pk = 'primary()' in line
                    tables[tbl]['columns'][col] = {'type': typ, 'nullable': nullable, 'default': default, 'pk': pk, 'unique': unique}
                    if pk:
                        tables[tbl]['pk'].append(col)
            m21 = foreign_re2.search(line)
            if m21:
                col, ref, on = m21.groups()
                tables[tbl]['fks'].append({'column': col, 'references': ref, 'on': on})
            m1 = foreign_re1.search(line)
            if m1 and '->constrained' in line:
                col, ref_tbl, ref_col = m1.groups()
                if ref_tbl is None:
                    ref_tbl = col[:-3] if col.endswith('_id') else None
                if ref_col is None:
                    ref_col = 'id'
                if ref_tbl:
                    tables[tbl]['fks'].append({'column': col, 'references': ref_col, 'on': ref_tbl})

models = {}
for dirpath, dirnames, filenames in os.walk(os.path.join(root, 'app', 'Models')):
    for fname in sorted(filenames):
        if not fname.endswith('.php'):
            continue
        path = os.path.join(dirpath, fname)
        relpath = os.path.relpath(path, root)
        text = open(path, encoding='utf-8', errors='ignore').read()
        mclass = re.search(r'class\s+([A-Za-z_][A-Za-z0-9_]*)', text)
        if not mclass:
            continue
        klass = mclass.group(1)
        models[klass] = {'file': relpath, 'relations': []}
        for line in text.splitlines():
            line = line.strip()
            if 'return $this->' in line and '(' in line and ');' in line:
                rel = re.search(r"return \$this->(hasOne|hasMany|belongsTo|belongsToMany|hasManyThrough|morphTo|morphMany|morphOne)\((.*)\)", line)
                if not rel:
                    continue
                kind, args = rel.groups()
                parts = []
                cur = ''
                depth = 0
                quote = None
                for c in args:
                    if quote:
                        if c == quote:
                            quote = None
                        cur += c
                    elif c in '"\'':
                        quote = c
                        cur += c
                    elif c == ',' and depth == 0:
                        parts.append(cur.strip())
                        cur = ''
                    else:
                        if c == '(':
                            depth += 1
                        elif c == ')':
                            depth -= 1
                        cur += c
                if cur.strip():
                    parts.append(cur.strip())
                related = None
                foreign = None
                owner = None
                pivot = None
                if parts:
                    p0 = parts[0]
                    mname = re.search(r'([A-Za-z_][A-Za-z0-9_\\\\]*)::class', p0)
                    if mname:
                        related = mname.group(1).split('\\')[-1]
                    else:
                        mname = re.search(r"['\"]([A-Za-z_][A-Za-z0-9_\\\\]*)['\"]", p0)
                        if mname:
                            related = mname.group(1).split('\\')[-1]
                if len(parts) >= 2:
                    p1 = parts[1]
                    mforeign = re.search(r"['\"]([A-Za-z_][A-Za-z0-9_]*)['\"]", p1)
                    if mforeign:
                        foreign = mforeign.group(1)
                if len(parts) >= 3:
                    p2 = parts[2]
                    mowner = re.search(r"['\"]([A-Za-z_][A-Za-z0-9_]*)['\"]", p2)
                    if mowner:
                        owner = mowner.group(1)
                if kind == 'belongsToMany' and len(parts) >= 4:
                    p3 = parts[3]
                    mpivot = re.search(r"['\"]([A-Za-z_][A-Za-z0-9_]*)['\"]", p3)
                    if mpivot:
                        pivot = mpivot.group(1)
                models[klass]['relations'].append({'type': kind, 'related': related, 'foreign_key': foreign, 'local_key': owner, 'pivot': pivot, 'raw': line})

print(json.dumps({'tables': tables, 'models': models}, indent=2))
