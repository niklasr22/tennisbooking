import { defineConfig } from "vite";
import react from "@vitejs/plugin-react";

export default defineConfig({
  plugins: [react()],
  server: {
    port: 3000,
  },
  test: {
    globals: true,
    environment: "jsdom",
    env: {
      VITE_API_ENDPOINT: "http://localhost/",
      VITE_PAYPAL_CLIENT_ID: "test-client-id",
    },
  },
});
