/*! 
 * Live2D Widget (custom - Japanese tips)
 * https://github.com/stevenjoezhang/live2d-widget
 */
import { initWidget } from 'https://fastly.jsdelivr.net/npm/live2d-widgets@1.0.0-rc.6/dist/waifu-tips.js';

const live2d_path = 'https://fastly.jsdelivr.net/npm/live2d-widgets@1.0.0-rc.6/dist/';

// Load CSS
const link = document.createElement('link');
link.rel = 'stylesheet';
link.href = live2d_path + 'waifu.css';
document.head.appendChild(link);

// Load live2d renderer
const script = document.createElement('script');
script.src = live2d_path + 'live2d.min.js';
document.head.appendChild(script);

// Init with Japanese tips
initWidget({
  waifuPath: '/waifu-tips.json',
  cdnPath: 'https://fastly.jsdelivr.net/gh/fghrsh/live2d_api/',
  cubism2Path: live2d_path + 'live2d.min.js',
  cubism5Path: 'https://cubism.live2d.com/sdk-web/cubismcore/live2dcubismcore.min.js',
  tools: ['hitokoto', 'asteroids', 'switch-model', 'switch-texture', 'photo', 'info', 'quit'],
  logLevel: 'warn',
  drag: false,
});
