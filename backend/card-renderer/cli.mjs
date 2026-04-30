#!/usr/bin/env node
import fs from 'fs';
import { renderCard } from './renderer.mjs';

async function main() {
  const raw = fs.readFileSync(0, 'utf8').trim();
  if (!raw) {
    console.error(JSON.stringify({ ok: false, error: 'empty stdin' }));
    process.exit(1);
  }
  let opts;
  try {
    opts = JSON.parse(raw);
  } catch (e) {
    console.error(JSON.stringify({ ok: false, error: 'invalid json: ' + e.message }));
    process.exit(1);
  }
  try {
    const pathOut = await renderCard(opts);
    console.log(JSON.stringify({ ok: true, path: pathOut }));
  } catch (e) {
    console.error(JSON.stringify({ ok: false, error: e.message || String(e) }));
    process.exit(1);
  }
}

main();
