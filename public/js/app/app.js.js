import Echo from "laravel-echo";
import Pusher from "pusher-js";

// window.Pusher = Pusher;

// window.Echo = new Echo({
//     broadcaster: "pusher",
//     key: import.meta.env.VITE_PUSHER_APP_KEY,
//     cluster: import.meta.env.VITE_PUSHER_APP_CLUSTER,
//     forceTLS: true,
//     encrypted: true,
// });

// Debug: Pastikan Echo terload
console.log("Echo loaded:", typeof Echo);

window.Echo = new Echo({
    broadcaster: "reverb",
    key: import.meta.env.VITE_REVERB_APP_KEY || "app_key_abcdef123456",
    wsHost: import.meta.env.VITE_REVERB_HOST || window.location.hostname,
    wsPort: import.meta.env.VITE_REVERB_PORT || 8080,
    wssPort: import.meta.env.VITE_REVERB_PORT || 8080,
    forceTLS: false,
    enabledTransports: ["ws", "wss"],
});

// Listen untuk private notifications
window.Echo.private(`App.Models.User.${window.userId}`).notification(
    (notification) => {
        console.log("🔔 Real-time notification received:", notification);

        // Trigger Filament notification
    }
);

// Debug langsung setelah inisialisasi
console.log("Echo initialized:", window.Echo);

// Tambahkan event listeners untuk debug
window.Echo.connector.socket.on("connect", () => {
    console.log("✅ Connected to Reverb server");
});

window.Echo.connector.socket.on("error", (error) => {
    console.error("❌ Reverb connection error:", error);
});

window.Echo.connector.socket.on("disconnect", () => {
    console.log("🔌 Disconnected from Reverb server");
});

// Test connection manually
setTimeout(() => {
    console.log(
        "Echo connector state:",
        window.Echo.connector.socket.connected
    );
}, 2000);
