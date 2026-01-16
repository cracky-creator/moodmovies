export function showNotif(message, type = "error") {
    console.log("showNotif appelé avec :", message, type); // <- TEST
    const container = document.querySelector(".notif-container");
    if (!container) return;

    const notif = document.createElement("p");
    notif.className = `notif ${type}`;
    notif.textContent = message;

    container.appendChild(notif);

    setTimeout(() => {
        notif.style.opacity = "0";
        setTimeout(() => notif.remove(), 300);
    }, 3000);
};

// Utility pour supprimer toutes les notifications avant d'en créer une nouvelle
export function clearNotifs() {
    const notifContainer = document.querySelector('.notif-container');
    if (notifContainer) {
        notifContainer.innerHTML = '';
    }
};
