import Echo from "laravel-echo";
// import Pusher from "pusher-js";

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
const userId = document.querySelector('meta[name="user-id"]').content;

console.log({ userId });
window.Echo.private(`App.Models.User.${window.userId}`).listen(
    ".NotificationSent",
    (e) => {
        new FilamentNotification()
            .title(e?.title)
            .body(e?.body)
            .success()
            .send();
        console.log("Notif Laravel:", e);
    }
);

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

document.addEventListener("DOMContentLoaded", function () {
    var expense = document.getElementById("expense");
    var bank_deposit = document.getElementById("bank_deposit");
    var invoice_nominal = document.getElementById("invoice_nominal");
    var start_balance = document.getElementById("start_balance");
    var today_income = document.getElementById("today_income");
    var end_balance = document.getElementById("end_balance");
    var total_deposit = document.getElementById("total_deposit");
    var grand_total = document.getElementById("grand_total");

    // Function to update total income
    function updateTotalIncome() {
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
        console.log({ end_balance });
        var isBelowZero = end_balanceValue < 0 || total_depositValue < 0;
        // document.getElementById("confirm").disabled = isBelowZero;
    }
    expense.addEventListener("input", updateTotalIncome);
    bank_deposit.addEventListener("input", updateTotalIncome);
    invoice_nominal.addEventListener("input", updateTotalIncome);
    start_balance.addEventListener("input", updateTotalIncome);
    today_income.addEventListener("input", updateTotalIncome);
    end_balance.addEventListener("input", updateTotalIncome);
    total_deposit.addEventListener("input", updateTotalIncome);
    updateTotalIncome();
});
