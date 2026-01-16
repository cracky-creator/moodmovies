'use strict';

import { gsap } from "gsap";
import { ScrollTrigger } from "gsap/ScrollTrigger";
gsap.registerPlugin(ScrollTrigger);

import { showNotif } from './notifications.js';
import { clearNotifs } from './notifications.js';

export function registerForm() {

    // ======================================================
    // STATE
    // ======================================================
    const registerState = {
        username: "",
        email: "",
        age: "",
        moods: [],
        genres: [],
        usernameAvailable: false,
        emailAvailable: false,
        ageValid: false
    };

    const MOOD_LIMIT = 3;
    const GENRE_LIMIT = 4;
    let currentStep = 0; // étape initiale

    const screenWidth = window.innerWidth;
    let stepGap;
    if (screenWidth < 768) {
        stepGap = 16;
    } else if (screenWidth < 1100) {
        stepGap = 18;
    } else {
        stepGap = 20;
    }
    
    const debounceTimers = {};
    let checkingAvailability = false;

    // ======================================================
    // ELEMENTS
    // ======================================================
    const inputUsername = document.querySelector('#username');
    const inputEmail = document.querySelector('#email');
    const inputAge = document.querySelector('#age');

    const moodCheckboxes = document.querySelectorAll('input[name="moods[]"]');
    const genreCheckboxes = document.querySelectorAll('input[name="genres[]"]');

    const moodCounter = document.querySelector('.form__mood-increment');
    const genreCounter = document.querySelector('.form__genre-increment');

    const formGroups = document.querySelectorAll('.form__group');
    const registerWrapper = document.querySelector('.form__wrapper');
    const registerNext = document.querySelector('.form__btn-next');
    const registerPrev = document.querySelector('.form__btn-return');
    const registerSubmit = document.querySelector('.form__btn-submit');
    const linkReturn = document.querySelector('.form__link-return');

    const bars = document.querySelectorAll('.register__progress-bar .bar');
    const globalCounter = document.querySelector('.progress-counter-increment');

    // ======================================================
    // HELPERS
    // ======================================================
    function isValidEmail(email) {
        return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email);
    }

    function isValidAge(age) {
        const num = parseInt(age, 10);
        return !isNaN(num) && num >= 10 && num <= 120;
    }

    function debounceCheck(type, value, callback) {
        clearTimeout(debounceTimers[type]);
        debounceTimers[type] = setTimeout(async () => {
            const available = await checkAvailability(type, value);
            callback(available);
        }, 500);
    }

    async function checkAvailability(type, value) {
        if (!value) return false;
        try {
            const res = await fetch(`actions/register-check-user.php?${type}=${encodeURIComponent(value)}`);
            const data = await res.json();
            return data.available;
        } catch (err) {
            console.error(err);
            return false;
        }
    }

    function updateInputState(input, valid) {
        if (valid) {
            input.classList.remove('invalid');
            input.classList.add('valid');
        } else {
            input.classList.remove('valid');
            input.classList.add('invalid');
        }
    }

    function updateSummary() {
        document.querySelector('.username').textContent = inputUsername.value || "-";
        document.querySelector('.user-mail').textContent = inputEmail.value || "-";
        document.querySelector('.user-age').textContent = inputAge.value || "-";

        updateList(moodCheckboxes, document.querySelector('.form__mood-list'));
        updateList(genreCheckboxes, document.querySelector('.form__genre-list'));
    }

    function updateList(checkboxes, container) {
        let ul = container.querySelector('ul.form__list');
        const checked = [...checkboxes].filter(c => c.checked);

        if (checked.length === 0) {
            if (ul) ul.remove();
            return;
        }

        if (!ul) {
            ul = document.createElement('ul');
            ul.classList.add('form__list');
            container.appendChild(ul);
        }

        ul.innerHTML = "";
        checked.forEach(box => {
            const li = document.createElement('li');
            li.classList.add('form__list__el');
            const p = document.createElement('p');
            p.textContent = document.querySelector(`label[for="${box.id}"]`).textContent;
            li.appendChild(p);
            ul.appendChild(li);
        });
    }

    function shakeButton(btn) {
        btn.classList.add('shake');
        setTimeout(() => btn.classList.remove('shake'), 300);
    }

    // ======================================================
    // VALIDATION
    // ======================================================
    function validateStep(step) {
        if (step === 0) {
            return (
                registerState.username.trim() !== "" &&
                registerState.email.trim() !== "" &&
                registerState.age.trim() !== "" &&
                registerState.usernameAvailable &&
                registerState.emailAvailable &&
                registerState.ageValid
            );
        }
        if (step === 1) return registerState.moods.length === MOOD_LIMIT;
        if (step === 2) return registerState.genres.length === GENRE_LIMIT;
        return true;
    }

    function updateNextButtonState() {
        if (validateStep(currentStep) && !checkingAvailability) {
            registerNext.classList.add('active');
        } else {
            registerNext.classList.remove('active');
        }
    }

    function updateRegisterWrapper() {
        const stepPx = registerWrapper.offsetWidth + stepGap;
        const translateX = -currentStep * stepPx;
        formGroups.forEach(group => {
            gsap.to(group, { x: translateX, duration: 0.5, ease: "power2.out" });
        });
    }

    function updateProgress() {
        bars.forEach((bar, index) => {
            if (index <= currentStep) bar.classList.add('bar--active');
            else bar.classList.remove('bar--active');
        });
        globalCounter.textContent = currentStep + 1; // afficher 1..4
    }

    function updateReturnButtons() {
        if (currentStep === 0) {
            registerPrev.classList.add('hidden');
            linkReturn.classList.remove('hidden');
        } else if (currentStep === 1) {
            registerPrev.classList.remove('hidden');
            linkReturn.classList.add('hidden');
        } else {
            registerPrev.classList.remove('hidden');
            linkReturn.classList.add('hidden');
        }
    }

    // ======================================================
    // INPUT EVENTS
    // ======================================================

    // Empêcher les espaces dans le pseudo
    inputUsername.addEventListener('input', () => {
        inputUsername.value = inputUsername.value.replace(/\s+/g, "");
    });

    // Empêcher les espaces dans le mot de passe
    const inputPassword = document.querySelector('#password');
    inputPassword.addEventListener('input', () => {
        inputPassword.value = inputPassword.value.replace(/\s+/g, "");
    });

    inputAge.addEventListener('input', () => {
        inputAge.value = inputAge.value.replace(/[^0-9]/g, "");
        registerState.age = inputAge.value;
        registerState.ageValid = isValidAge(inputAge.value);
        updateInputState(inputAge, registerState.ageValid);
        updateSummary();
        updateNextButtonState();
    });

    inputUsername.addEventListener('input', () => {
        registerState.username = inputUsername.value;
        checkingAvailability = true;
        debounceCheck('username', inputUsername.value, result => {
            registerState.usernameAvailable = result;
            updateInputState(inputUsername, result);
            checkingAvailability = false;
            updateNextButtonState();
        });
        updateSummary();
        updateNextButtonState();
    });

    inputEmail.addEventListener('input', () => {
        registerState.email = inputEmail.value;
        if (!isValidEmail(inputEmail.value)) {
            registerState.emailAvailable = false;
            updateInputState(inputEmail, false);
            updateNextButtonState();
            return;
        }
        checkingAvailability = true;
        debounceCheck('email', inputEmail.value, result => {
            registerState.emailAvailable = result;
            updateInputState(inputEmail, result);
            checkingAvailability = false;
            updateNextButtonState();
        });
        updateSummary();
        updateNextButtonState();
    });

    // ======================================================
    // CHECKBOXES
    // ======================================================
    function limitCheckboxes(checkboxes, maxAllowed, counterEl) {
        checkboxes.forEach(cb => {
            cb.addEventListener("change", () => {
                const checked = [...checkboxes].filter(c => c.checked);
                if (checked.length > maxAllowed) {
                    cb.checked = false;
                    counterEl.textContent = checked.length - 1;
                    clearNotifs();
                    showNotif(`Vous ne pouvez sélectionner que ${maxAllowed} éléments`, "error");
                    return;
                }
                counterEl.textContent = checked.length;
                if (checkboxes === moodCheckboxes) registerState.moods = checked.map(x => x.value);
                else registerState.genres = checked.map(x => x.value);
                updateSummary();
                updateNextButtonState();
            });
        });
    }

    limitCheckboxes(moodCheckboxes, MOOD_LIMIT, moodCounter);
    limitCheckboxes(genreCheckboxes, GENRE_LIMIT, genreCounter);

    // ======================================================
    // NAVIGATION
    // ======================================================
    registerNext.addEventListener('click', e => {
        e.preventDefault();
        clearNotifs();
        let canProceed = true;

        if (currentStep === 0) {

            // 1️⃣ Champs vides → une seule notif et STOP
            if (
                !registerState.username.trim() ||
                !registerState.email.trim() ||
                !registerState.age.trim()
            ) {
                clearNotifs();
                showNotif("Tous les champs ne sont pas remplis", "error");
                shakeButton(registerNext);
                return; // ⛔ on bloque tout le reste
            }

            // 2️⃣ Pseudo déjà utilisé
            if (!registerState.usernameAvailable) {
                clearNotifs();
                showNotif("Pseudo déjà utilisé", "error");
                shakeButton(registerNext);
                return;
            }

            // 3️⃣ Email invalide ou déjà utilisé
            if (!isValidEmail(registerState.email) || !registerState.emailAvailable) {
                clearNotifs();
                showNotif("Email invalide ou déjà utilisé", "error");
                shakeButton(registerNext);
                return;
            }

            // 4️⃣ Âge invalide
            if (!registerState.ageValid) {
                clearNotifs();
                showNotif("Âge trop jeune (10+) ou improbable", "error");
                shakeButton(registerNext);
                return;
            }
        }


        if (currentStep === 1 && registerState.moods.length !== MOOD_LIMIT) {
            clearNotifs();
            showNotif(`Veuillez sélectionner ${MOOD_LIMIT} moods`, "error");
            canProceed = false;
        }
        if (currentStep === 2 && registerState.genres.length !== GENRE_LIMIT) {
            clearNotifs();
            showNotif(`Veuillez sélectionner ${GENRE_LIMIT} genres`, "error");
            canProceed = false;
        }

        if (canProceed) {
            if (currentStep < formGroups.length - 1) currentStep++;

            // Gestion du bouton submit
            if (currentStep === formGroups.length - 1) {
                registerNext.classList.add('hidden');
                registerSubmit.classList.remove('hidden');
            } else {
                registerNext.classList.remove('hidden');
                registerSubmit.classList.add('hidden');
            }

            updateRegisterWrapper();
            updateProgress();
            updateReturnButtons();
        }

        updateNextButtonState();
    });

    registerPrev.addEventListener('click', e => {
        e.preventDefault();
        if (currentStep > 0) currentStep--;
        if (currentStep < formGroups.length - 1) {
            registerSubmit.classList.add('hidden');
            registerNext.classList.remove('hidden');
        }
        updateRegisterWrapper();
        updateProgress();
        updateReturnButtons();
        updateNextButtonState();
    });

    // ======================================================
    // INITIAL UPDATE
    // ======================================================
    updateSummary();
    updateProgress();
    updateNextButtonState();
    updateReturnButtons(); // <-- mise à jour initiale
}
