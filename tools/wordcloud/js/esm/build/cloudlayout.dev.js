var __defProp = Object.defineProperty;
var __name = (target, value) => __defProp(target, "name", { value, configurable: true });
/**
 * Word cloud layout.
 *
 * TypeScript port of the layout of d3-cloud 1.2.9 (https://github.com/jasondavies/d3-cloud), see thirdpartylibs.xml.
 * Copyright (c) 2013, Jason Davies, https://www.jasondavies.com/. Released under the BSD 3-Clause license.
 * The algorithm is due to Jonathan Feinberg, https://s3.amazonaws.com/static.mrfeinberg.com/bv_ch03.pdf.
 *
 * @module     mootimetertool_wordcloud/cloudlayout
 * @copyright  2026 ISB Bayern
 * @author     Dr. Peter Mayer
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
const RADIANS = Math.PI / 180;
const CW = 1 << 11 >> 5;
const CH = 1 << 11;
function getContext() {
  const canvas = document.createElement("canvas");
  const context = canvas.getContext("2d", { willReadFrequently: true });
  canvas.width = canvas.height = 1;
  const ratio = Math.sqrt(context.getImageData(0, 0, 1, 1).data.length >> 2);
  canvas.width = (CW << 5) / ratio;
  canvas.height = CH / ratio;
  context.fillStyle = context.strokeStyle = "red";
  return { context, ratio };
}
__name(getContext, "getContext");
function cloudSprite(contextandratio, data, di, font) {
  if (data[di].sprite) {
    return;
  }
  const { context: c, ratio } = contextandratio;
  c.clearRect(0, 0, (CW << 5) / ratio, CH / ratio);
  let x = 0;
  let y = 0;
  let maxh = 0;
  const n = data.length;
  --di;
  while (++di < n) {
    const d = data[di];
    c.save();
    c.font = `normal normal ${~~((d.size + 1) / ratio)}px ${font}`;
    const metrics = c.measureText(d.text);
    const anchor = -Math.floor(metrics.width / 2);
    let w = (metrics.width + 1) * ratio;
    let h = d.size << 1;
    if (d.rotate) {
      const sr = Math.sin(d.rotate * RADIANS);
      const cr = Math.cos(d.rotate * RADIANS);
      const wcr = w * cr;
      const wsr = w * sr;
      const hcr = h * cr;
      const hsr = h * sr;
      w = Math.max(Math.abs(wcr + hsr), Math.abs(wcr - hsr)) + 31 >> 5 << 5;
      h = ~~Math.max(Math.abs(wsr + hcr), Math.abs(wsr - hcr));
    } else {
      w = w + 31 >> 5 << 5;
    }
    if (h > maxh) {
      maxh = h;
    }
    if (x + w >= CW << 5) {
      x = 0;
      y += maxh;
      maxh = 0;
    }
    if (y + h >= CH) {
      break;
    }
    c.translate((x + (w >> 1)) / ratio, (y + (h >> 1)) / ratio);
    if (d.rotate) {
      c.rotate(d.rotate * RADIANS);
    }
    c.fillText(d.text, anchor, 0);
    if (d.padding) {
      c.lineWidth = 2 * d.padding;
      c.strokeText(d.text, anchor, 0);
    }
    c.restore();
    d.width = w;
    d.height = h;
    d.xoff = x;
    d.yoff = y;
    d.x1 = w >> 1;
    d.y1 = h >> 1;
    d.x0 = -d.x1;
    d.y0 = -d.y1;
    d.hasText = true;
    x += w;
  }
  const pixels = c.getImageData(0, 0, (CW << 5) / ratio, CH / ratio).data;
  const sprite = [];
  while (--di >= 0) {
    const d = data[di];
    if (!d.hasText) {
      continue;
    }
    const w = d.width;
    const w32 = w >> 5;
    let h = d.y1 - d.y0;
    for (let i = 0; i < h * w32; i++) {
      sprite[i] = 0;
    }
    x = d.xoff;
    y = d.yoff;
    let seen = 0;
    let seenrow = -1;
    for (let j = 0; j < h; j++) {
      for (let i = 0; i < w; i++) {
        const k = w32 * j + (i >> 5);
        const m = pixels[(y + j) * (CW << 5) + (x + i) << 2] ? 1 << 31 - i % 32 : 0;
        sprite[k] |= m;
        seen |= m;
      }
      if (seen) {
        seenrow = j;
      } else {
        d.y0++;
        h--;
        j--;
        y++;
      }
    }
    d.y1 = d.y0 + seenrow;
    d.sprite = sprite.slice(0, (d.y1 - d.y0) * w32);
  }
}
__name(cloudSprite, "cloudSprite");
function cloudCollide(tag, board, sw) {
  sw >>= 5;
  const sprite = tag.sprite;
  const w = tag.width >> 5;
  const lx = tag.x - (w << 4);
  const sx = lx & 127;
  const msx = 32 - sx;
  const h = tag.y1 - tag.y0;
  let x = (tag.y + tag.y0) * sw + (lx >> 5);
  for (let j = 0; j < h; j++) {
    let last = 0;
    for (let i = 0; i <= w; i++) {
      if ((last << msx | (i < w ? (last = sprite[j * w + i]) >>> sx : 0)) & board[x + i]) {
        return true;
      }
    }
    x += sw;
  }
  return false;
}
__name(cloudCollide, "cloudCollide");
function cloudBounds(bounds, d) {
  const [b0, b1] = bounds;
  b0.x = Math.min(b0.x, d.x + d.x0);
  b0.y = Math.min(b0.y, d.y + d.y0);
  b1.x = Math.max(b1.x, d.x + d.x1);
  b1.y = Math.max(b1.y, d.y + d.y1);
}
__name(cloudBounds, "cloudBounds");
function collideRects(a, b) {
  return a.x + a.x1 > b[0].x && a.x + a.x0 < b[1].x && a.y + a.y1 > b[0].y && a.y + a.y0 < b[1].y;
}
__name(collideRects, "collideRects");
function archimedeanSpiral(width, height) {
  const e = width / height;
  return (t) => {
    t *= 0.1;
    return [e * t * Math.cos(t), t * Math.sin(t)];
  };
}
__name(archimedeanSpiral, "archimedeanSpiral");
function layoutCloud(words, { width, height, font, padding }) {
  let timer;
  const state = { stopped: false };
  const stop = /* @__PURE__ */ __name(() => {
    state.stopped = true;
    window.clearTimeout(timer);
  }, "stop");
  const promise = new Promise((resolve) => {
    const contextandratio = getContext();
    const board = new Int32Array((width >> 5) * height);
    const data = words.map((word) => ({
      ...word,
      size: ~~word.size,
      padding,
      x: 0,
      y: 0,
      x0: 0,
      y0: 0,
      x1: 0,
      y1: 0,
      width: 0,
      height: 0,
      xoff: 0,
      yoff: 0,
      hasText: false,
      sprite: null
    })).sort((a, b) => b.size - a.size);
    const tags = [];
    let bounds = null;
    let i = -1;
    const place = /* @__PURE__ */ __name((tag) => {
      const startx = tag.x;
      const starty = tag.y;
      const maxdelta = Math.sqrt(width * width + height * height);
      const spiral = archimedeanSpiral(width, height);
      const dt = Math.random() < 0.5 ? 1 : -1;
      let t = -dt;
      for (; ; ) {
        t += dt;
        const [sx, sy] = spiral(t);
        const dx = ~~sx;
        const dy = ~~sy;
        if (Math.min(Math.abs(dx), Math.abs(dy)) >= maxdelta) {
          return false;
        }
        tag.x = startx + dx;
        tag.y = starty + dy;
        if (tag.x + tag.x0 < 0 || tag.y + tag.y0 < 0 || tag.x + tag.x1 > width || tag.y + tag.y1 > height) {
          continue;
        }
        if (bounds && !collideRects(tag, bounds) || cloudCollide(tag, board, width)) {
          continue;
        }
        const sprite = tag.sprite;
        const w = tag.width >> 5;
        const sw = width >> 5;
        const lx = tag.x - (w << 4);
        const sxoff = lx & 127;
        const msx = 32 - sxoff;
        const h = tag.y1 - tag.y0;
        let x = (tag.y + tag.y0) * sw + (lx >> 5);
        for (let j = 0; j < h; j++) {
          let last = 0;
          for (let k = 0; k <= w; k++) {
            board[x + k] |= last << msx | (k < w ? (last = sprite[j * w + k]) >>> sxoff : 0);
          }
          x += sw;
        }
        return true;
      }
    }, "place");
    const step = /* @__PURE__ */ __name(() => {
      const start = Date.now();
      while (Date.now() - start < 50 && ++i < data.length && !state.stopped) {
        const d = data[i];
        d.x = width * (Math.random() + 0.5) >> 1;
        d.y = height * (Math.random() + 0.5) >> 1;
        cloudSprite(contextandratio, data, i, font);
        if (d.hasText && place(d)) {
          if (bounds) {
            cloudBounds(bounds, d);
          } else {
            bounds = [{ x: d.x + d.x0, y: d.y + d.y0 }, { x: d.x + d.x1, y: d.y + d.y1 }];
          }
          tags.push({ ...d, sprite: null, x: d.x - (width >> 1), y: d.y - (height >> 1) });
        }
      }
      if (state.stopped) {
        return;
      }
      if (i >= data.length) {
        resolve(tags);
      } else {
        timer = window.setTimeout(step, 0);
      }
    }, "step");
    step();
  });
  return { promise, stop };
}
__name(layoutCloud, "layoutCloud");
export {
  layoutCloud
};
//# sourceMappingURL=cloudlayout.dev.js.map
