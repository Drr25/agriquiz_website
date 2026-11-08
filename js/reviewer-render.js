//render of reviewer

function escHtmlShared(str) {
  const d = document.createElement('div');
  d.textContent = str;
  return d.innerHTML;
}

function inlineMdShared(text) {
  let raw = text;
  let prev = 0;
  const segments = [];
  const reImg = /!\[([^\]]*)\]\(([^)]+)\)/g;
  let m;
  while ((m = reImg.exec(raw)) !== null) {
    if (m.index > prev) segments.push({ type: 'text', val: raw.slice(prev, m.index) });
    segments.push({ type: 'img', alt: m[1], url: m[2] });
    prev = m.index + m[0].length;
  }
  if (prev < raw.length) segments.push({ type: 'text', val: raw.slice(prev) });
  if (!segments.length) segments.push({ type: 'text', val: raw });

  let result = '';
  for (const seg of segments) {
    if (seg.type === 'img') {
      const safeUrl = seg.url.replace(/"/g, '%22');
      const safeAlt = seg.alt.replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
      result += `<span class="rev-img-wrap"><img src="${safeUrl}" alt="${safeAlt}" loading="lazy" onclick="if(window.showReviewerImage)showReviewerImage('${safeUrl.replace(/'/g, "\\'")}')" onerror="this.parentElement.style.display='none'"><span class="rev-img-caption">${safeAlt}</span></span>`;
    } else {
      let t = seg.val.replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;');
      t = t
        .replace(/`([^`]+)`/g, '<code>$1</code>')
        .replace(/\*\*\*(.+?)\*\*\*/g, '<strong><em>$1</em></strong>')
        .replace(/\*\*(.+?)\*\*/g, '<strong>$1</strong>')
        .replace(/\*(.+?)\*/g, '<em>$1</em>')
        .replace(/___(.+?)___/g, '<strong><em>$1</em></strong>')
        .replace(/__(.+?)__/g, '<strong>$1</strong>')
        .replace(/_(.+?)_/g, '<em>$1</em>')
        .replace(/\[([^\]]+)\]\(([^)]+)\)/g, '<a href="$2" target="_blank" rel="noopener">$1</a>');
      result += t;
    }
  }
  return result;
}

function parseIntoSectionsShared(md) {
  if (!md) return [];
  const lines = md.split('\n');
  const sections = [];
  let currentTitle = null;
  let currentLines = [];

  function flushSection() {
    if (currentTitle !== null || currentLines.length) {
      sections.push({ title: currentTitle || '', bodyHtml: renderBodyLinesShared(currentLines) });
    }
  }

  for (let i = 0; i < lines.length; i++) {
    const line = lines[i];
    const hm = line.match(/^#\s+(.+)/);
    if (hm) {
      flushSection();
      currentTitle = hm[1].trim();
      currentLines = [];
    } else {
      currentLines.push(line);
    }
  }
  flushSection();
  return sections.filter(s => s.title || s.bodyHtml.trim());
}

function renderBodyLinesShared(lines) {
  let html = '';
  let i = 0;

  function looksLikePlainHeading(line) {
    const t = line.trim();
    if (!t || t.length > 90) return false;
    if (t.startsWith('**') || t.startsWith('__') || t.startsWith('*') || t.startsWith('_')) return false;
    if (t.endsWith('.') || t.endsWith(',') || t.endsWith(';')) return false;
    if (/^[A-Z][A-Z\s\d\-\/\(\)]{4,}$/.test(t)) return true;
    if (t.endsWith(':') && t.length < 70) return true;
    const words = t.split(/\s+/);
    if (words.length >= 2 && words.length <= 10) {
      const capCount = words.filter(w => /^[A-Z]/.test(w)).length;
      if (capCount / words.length >= 0.7) return true;
    }
    return false;
  }

  function isStandaloneImage(line) {
    return /^!\[[^\]]*\]\([^)]+\)\s*$/.test(line.trim());
  }

  while (i < lines.length) {
    const line = lines[i];
    const trimmed = line.trim();

    if (trimmed.startsWith('```')) {
      let code = '';
      i++;
      while (i < lines.length && !lines[i].trim().startsWith('```')) {
        code += lines[i].replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;') + '\n';
        i++;
      }
      html += `<pre><code>${code.trimEnd()}</code></pre>`;
      i++;
      continue;
    }

    if (/^(\*{3,}|-{3,}|_{3,})\s*$/.test(trimmed)) { html += '<hr>'; i++; continue; }

    let hm;
    if ((hm = line.match(/^#{3,}\s+(.+)/))) { html += `<div class="rh3">${inlineMdShared(hm[1])}</div>`; i++; continue; }
    if ((hm = line.match(/^##\s+(.+)/))) { html += `<div class="rh2">${inlineMdShared(hm[1])}</div>`; i++; continue; }

    if (line.startsWith('> ')) {
      let bq = '';
      while (i < lines.length && lines[i].startsWith('> ')) { bq += inlineMdShared(lines[i].slice(2)) + ' '; i++; }
      html += `<blockquote>${bq.trim()}</blockquote>`;
      continue;
    }

    if (/^[-*+]\s/.test(line)) {
      html += '<ul>';
      while (i < lines.length && /^[-*+]\s/.test(lines[i])) {
        html += `<li>${inlineMdShared(lines[i].replace(/^[-*+]\s/, ''))}</li>`;
        i++;
      }
      html += '</ul>';
      continue;
    }

    if (/^\d+[\.\)]\s/.test(line)) {
      html += '<ul>';
      while (i < lines.length) {
        const cur = lines[i];
        if (/^\d+[\.\)]\s/.test(cur)) {
          html += `<li>${inlineMdShared(cur.replace(/^\d+[\.\)]\s/, ''))}</li>`;
          i++;
        } else if (cur.trim() === '' && i + 1 < lines.length && /^\d+[\.\)]\s/.test(lines[i + 1])) {
          i++;
        } else {
          break;
        }
      }
      html += '</ul>';
      continue;
    }

    if (line.includes('|') && i + 1 < lines.length && /^\|?\s*[-:]+/.test(lines[i + 1])) {
      const headers = line.split('|').map(c => c.trim()).filter(Boolean);
      i += 2;
      html += '<div class="rev-table-wrap"><table><thead><tr>' + headers.map(h => `<th>${inlineMdShared(h)}</th>`).join('') + '</tr></thead><tbody>';
      while (i < lines.length && lines[i].trim() !== '' && lines[i].includes('|')) {
        const cells = lines[i].split('|').map(c => c.trim()).filter(Boolean);
        html += '<tr>' + cells.map(c => `<td>${inlineMdShared(c)}</td>`).join('') + '</tr>';
        i++;
      }
      html += '</tbody></table></div>';
      continue;
    }

    if (trimmed === '') { i++; continue; }

    if (isStandaloneImage(trimmed)) {
      html += `<div style="margin:16px 0;">${inlineMdShared(trimmed)}</div>`;
      i++;
      continue;
    }

    if (looksLikePlainHeading(trimmed)) {
      html += `<div class="rh2">${inlineMdShared(trimmed.replace(/:$/, ''))}</div>`;
      i++;
      continue;
    }

    let para = '';
    while (
      i < lines.length &&
      lines[i].trim() !== '' &&
      !/^#{1,6}\s/.test(lines[i]) &&
      !/^[-*+]\s/.test(lines[i]) &&
      !/^\d+[\.\)]\s/.test(lines[i]) &&
      !lines[i].startsWith('> ') &&
      !lines[i].trim().startsWith('```') &&
      !/^(\*{3,}|-{3,}|_{3,})\s*$/.test(lines[i].trim()) &&
      !(lines[i].includes('|') && i + 1 < lines.length && /^\|?\s*[-:]+/.test(lines[i + 1])) &&
      !looksLikePlainHeading(lines[i].trim())
    ) {
      para += lines[i] + ' ';
      i++;
    }
    if (para.trim()) html += `<p>${inlineMdShared(para.trim())}</p>`;
  }

  return html;
}

// Top-level entry point — call this from BOTH admin preview and student view.
function renderReviewerPreviewHTML(rawContent) {
  const sections = parseIntoSectionsShared(rawContent || '');
  if (!sections.length) {
    return '<p style="color:#94a3b8;font-style:italic;padding:10px 0;">No content yet.</p>';
  }
  return sections.map(s => {
    const heading = s.title ? `<div class="rh2">${escHtmlShared(s.title)}</div>` : '';
    return heading + s.bodyHtml;
  }).join('');
}