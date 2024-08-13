
with Vite Dev Server

```html
<script type="module" src="/@vite/client"></script>
<script type="module" src="/main.js"></script>
```

after build

```html
  <head>

    <link rel="stylesheet" href="/assets/index-824f0ed3.css">

    <!-- DETECT_MODERN_BROWSER_CODE -->
    <script type="module">
    try{import.meta.url;import("_").catch(()=>1);}catch(e){}window.__vite_is_modern_browser=true;
    </script>
    <!-- readable version with chatGPT -->
    <script type="module">
      try {
          // Check if the current environment supports the "import.meta" syntax
          const metaUrl = import.meta.url;
          // Attempt to import the "_" module and catch any errors
          import("_").catch(() => {});
      } catch (e) {
          // Do nothing if the "import" syntax or "import.meta" is not supported
      }

      // Set a global variable indicating that the browser is modern
      window.__vite_is_modern_browser = true;
    </script>




    <!-- DYNAMIC_FALLBACK_INLINE_CODE -->
    <script type="module">
    !function(){if(window.__vite_is_modern_browser)return;console.warn("vite: loading legacy build because dynamic import or import.meta.url is unsupported, syntax error above should be ignored");var e=document.getElementById("vite-legacy-polyfill"),n=document.createElement("script");n.src=e.src,n.onload=function(){System.import(document.getElementById('vite-legacy-entry').getAttribute('data-src'))},document.body.appendChild(n)}();
    </script>

    <!-- readable version with chatGPT -->
    <script type="module">
      (function() {
        if (window.__vite_is_modern_browser) {
            return;
        }

        console.warn("vite: loading legacy build because dynamic import or import.meta.url is unsupported, syntax error above should be ignored");

        var legacyPolyfill = document.getElementById("vite-legacy-polyfill");
        var script = document.createElement("script");
        script.src = legacyPolyfill.src;
        script.onload = function() {
            System.import(document.getElementById('vite-legacy-entry').getAttribute('data-src'))
        };
        document.body.appendChild(script);
      })();
    </script>




    <!-- SAFARI10_NO_MODULE_FIX -->
    <script nomodule>
    !function(){var e=document,t=e.createElement("script");if(!("noModule"in t)&&"onbeforeload"in t){var n=!1;e.addEventListener("beforeload",(function(e){if(e.target===t)n=!0;else if(!e.target.hasAttribute("nomodule")||!n)return;e.preventDefault()}),!0),t.type="module",t.src=".",e.head.appendChild(t),t.remove()}}();
    </script>

    <!-- readable version with chatGPT -->
    <script>
      (function() {
          var document = window.document;
          var script = document.createElement("script");

          // Check if the "nomodule" attribute is supported and the "onbeforeload" event is supported
          if (!("noModule" in script) && "onbeforeload" in script) {
              var nomoduleFound = false;

              // Add a "beforeload" event listener
              document.addEventListener("beforeload", function(event) {
                  if (event.target === script) {
                      nomoduleFound = true;
                  } else if (!event.target.hasAttribute("nomodule") || !nomoduleFound) {
                      return;
                  }
                  event.preventDefault();
              }, true);

              script.type = "module";
              script.src = ".";
              document.head.appendChild(script);
              script.remove();
          }
      })();
    </script>

    <script nomodule crossorigin id="vite-legacy-polyfill" src="/assets/polyfills-legacy-40963d34.js"></script>

    <script type="module" crossorigin src="/assets/index-bac46bd1.js"></script>
    
    <script nomodule crossorigin id="vite-legacy-entry" data-src="/assets/index-legacy-affdb848.js">
      System.import(document.getElementById('vite-legacy-entry').getAttribute('data-src'))
    </script>

  </head>

  <body>
    <!-- your content -->
  </body>
```
