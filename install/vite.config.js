import { defineConfig } from "vite";
import symfonyPlugin from "vite-plugin-symfony";

/* if you're using React */
// import react from '@vitejs/plugin-react';

export default defineConfig({
    plugins: [
        /* react(), // if you're using React */
        symfonyPlugin(),
    ],
    root: ".",
    base: "/build/",
    publicDir: false,
    build: {
        manifest: true,
        emptyOutDir: true,
        assetsDir: "",
        outDir: "./public/build",
        rollupOptions: {
            input: {
                app: "./assets/app.js"
            },
        },
    },
});
