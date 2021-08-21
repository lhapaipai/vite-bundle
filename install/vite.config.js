import { resolve } from "path";
import { unlinkSync, existsSync } from "fs";

/* if you're using React */
// import reactRefresh from "@vitejs/plugin-react-refresh";

const symfonyPlugin = {
    name: "symfony",
    configResolved(config) {
        if (config.env.DEV && config.build.manifest) {
            let buildDir = resolve(
                config.root,
                config.build.outDir,
                "manifest.json"
            );
            existsSync(buildDir) && unlinkSync(buildDir);
        }
    },
    configureServer(devServer) {
        let { watcher, ws } = devServer;
        watcher.add(resolve("templates/**/*.twig"));
        watcher.on("change", function (path) {
            if (path.endsWith(".twig")) {
                ws.send({
                    type: "full-reload",
                });
            }
        });
    },
};

export default {
    plugins: [
        /* reactRefresh(), // if you're using React */
        symfonyPlugin,
    ],
    server: {
        watch: {
            disableGlobbing: false,
        },
        fs: {
            strict: false,
            allow: [".."],
        },
    },
    root: "./assets",
    base: "/build/",
    build: {
        manifest: true,
        emptyOutDir: true,
        assetsDir: "",
        outDir: "../public/build/",
        rollupOptions: {
            input: ["./assets/app.js"],
        },
    },
};
