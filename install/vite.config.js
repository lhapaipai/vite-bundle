import { defineConfig } from "vite";
import symfonyPlugin from "vite-plugin-symfony";

/* if you're using React */
// import reactRefresh from "@vitejs/plugin-react-refresh";

export default defineConfig({
    plugins: [
        /* reactRefresh(), // if you're using React */
        symfonyPlugin(),
    ],
    root: "./assets",
    base: "/build/",
    build: {
        manifest: true,
        emptyOutDir: true,
        assetsDir: "",
        outDir: "../public/build/",
        rollupOptions: {
            input: {
                app: "./assets/app.js"
            },
        },
    },
});
