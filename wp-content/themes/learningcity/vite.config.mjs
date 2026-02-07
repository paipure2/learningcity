import path from "path";
import { defineConfig } from "vite";
import tailwindcss from "@tailwindcss/vite";

const ROOT = path.resolve("../../../");
const BASE = __dirname.replace(ROOT, "");

export default defineConfig({
    base: process.env.NODE_ENV === "production" ? `${BASE}/dist/` : BASE,
    server: {
        host: "0.0.0.0",
        port: 5173,
        cors: true,
        strictPort: true,
        https: false,
        hmr: {
            host: "localhost",
        },
    },
    plugins: [tailwindcss()],
    experimental: {
        renderBuiltUrl(filename, { hostType }) {
            if (hostType === "css") {
                return { relative: true };
            }
            return { relative: true };
        },
    },
    build: {
        manifest: true,
        assetsDir: ".",
        outDir: "dist",
        emptyOutDir: true,
        sourcemap: false,
        assetsInlineLimit: 0,
        minify: true,
        rollupOptions: {
            input: [
                "assets/scripts/scripts.js",
                // "assets/scripts/admin-scripts.js",
                "assets/styles/styles.css",
            ],
            output: {
                format: "es",
                entryFileNames: "[hash].js",
                chunkFileNames: "[hash].js",
                assetFileNames: (assetInfo) => {
                    const fileName = assetInfo.names?.[0] || "";
                    if (/\.(png|jpe?g|gif|svg|webp|avif)$/i.test(fileName)) {
                        return "images/[hash][extname]";
                    }
                    if (/\.(woff2?|ttf|eot|otf)$/i.test(fileName)) {
                        return "fonts/[hash][extname]";
                    }
                    if (/\.(css)$/i.test(fileName)) {
                        return "[hash][extname]";
                    }
                    return "others/[hash][extname]";
                },
            },
        },
    },
});
