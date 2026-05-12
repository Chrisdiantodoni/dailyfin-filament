import Echo from "laravel-echo";

// Reverb Echo setup
window.Echo = new Echo({
    broadcaster: "reverb",
    key: import.meta.env.VITE_REVERB_APP_KEY,
    wsHost: import.meta.env.VITE_REVERB_HOST,
    wsPort: import.meta.env.VITE_REVERB_PORT ?? 80,
    wssPort: import.meta.env.VITE_REVERB_PORT ?? 443,
    forceTLS: (import.meta.env.VITE_REVERB_SCHEME ?? "https") === "https",
    enabledTransports: ["ws", "wss"],
});

// Safe meta access
const metaUserId = document.querySelector('meta[name="user-id"]');
if (metaUserId) {
    const userId = metaUserId.content;
    console.log({ userId });
    window.Echo.private(`App.Models.User.${userId}`).listen(
        ".NotificationSent",
        (e) => {
            if (typeof FilamentNotification !== "undefined") {
                new FilamentNotification()
                    .title(e?.title)
                    .body(e?.body)
                    .success()
                    .send();
            }
            console.log("Notif Laravel:", e);
        },
    );
}

// Upload button helpers
document.addEventListener("livewire-upload-start", () => {
    const button = document.getElementById("create-button");
    if (button) {
        button.disabled = true;
        button.classList.add("opacity-50", "cursor-not-allowed");
    }
});

document.addEventListener("livewire-upload-finish", () => {
    const button = document.getElementById("create-button");
    if (button) {
        button.disabled = false;
        button.classList.remove("opacity-50", "cursor-not-allowed");
    }
});

document.addEventListener("livewire-upload-error", () => {
    const button = document.getElementById("create-button");
    if (button) {
        button.disabled = false;
        button.classList.remove("opacity-50", "cursor-not-allowed");
    }
});

// Cash calculation helpers (only on pages with these fields)
document.addEventListener("DOMContentLoaded", function () {
    var expense = document.getElementById("expense");
    var bank_deposit = document.getElementById("bank_deposit");
    var invoice_nominal = document.getElementById("invoice_nominal");
    var start_balance = document.getElementById("start_balance");
    var today_income = document.getElementById("today_income");
    var end_balance = document.getElementById("end_balance");
    var total_deposit = document.getElementById("total_deposit");

    if (
        !expense ||
        !bank_deposit ||
        !invoice_nominal ||
        !start_balance ||
        !today_income ||
        !end_balance ||
        !total_deposit
    ) {
        return; // Skip if not on cash form page
    }

    function updateTotalIncome() {
        if (!expense || !end_balance || !total_deposit) return;

        var expenseValue = parseFloat(expense.value) || 0;
        var bank_depositValue = parseFloat(bank_deposit.value) || 0;
        var invoice_nominalValue = parseFloat(invoice_nominal.value) || 0;
        var start_balanceValue = parseFloat(start_balance.value) || 0;
        var today_incomeValue = parseFloat(today_income.value) || 0;

        var end_balanceValue =
            start_balanceValue +
            today_incomeValue -
            expenseValue -
            bank_depositValue;

        end_balance.value =
            end_balanceValue.toLocaleString("en-US").replace(/,/g, ".") + ",00";

        var total_depositValue = end_balanceValue - invoice_nominalValue;
        total_deposit.value =
            total_depositValue.toLocaleString("en-US").replace(/,/g, ".") +
            ",00";

        var isBelowZero = end_balanceValue < 0 || total_depositValue < 0;
        console.log({ end_balance: end_balance.value, isBelowZero });
    }

    // Hanya pasang event listener jika elemen form ada di halaman tersebut
    if (expense) {
        expense.addEventListener("input", updateTotalIncome);
        bank_deposit.addEventListener("input", updateTotalIncome);
        invoice_nominal.addEventListener("input", updateTotalIncome);
        start_balance.addEventListener("input", updateTotalIncome);
        today_income.addEventListener("input", updateTotalIncome);
        end_balance.addEventListener("input", updateTotalIncome);
        total_deposit.addEventListener("input", updateTotalIncome);
        updateTotalIncome();
    }
});
