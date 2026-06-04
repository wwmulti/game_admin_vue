const originalWarn = console.warn;
console.warn = (...args) => {
  const shouldBlock = args.some(arg => {
    if (typeof arg === 'string') {
      return arg.includes('ElButton');
    }
    return false;
  });
  if (shouldBlock) {
    return; // 拦截讨厌的警告
  }
  originalWarn(...args);
};
import App from './App.vue'
import { createApp } from 'vue'
import { initStore } from './store'                 // Store
import { initRouter } from './router'               // Router
import language from './locales'                    // 国际化
import '@styles/core/tailwind.css'                  // tailwind
import '@styles/index.scss'                         // 样式
import '@utils/sys/console.ts'                      // 控制台输出内容
import { setupGlobDirectives } from './directives'
import { setupErrorHandle } from "@utils/sys/error-handle"
import * as globalUtils from '@/utils/common'
Object.assign(window, globalUtils)

document.addEventListener(
  'touchstart',
  function () {},
  { passive: false }
)

const app = createApp(App)
initStore(app)
initRouter(app)
setupGlobDirectives(app)
setupErrorHandle(app)

app.use(language)
app.mount('#app')