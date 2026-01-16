'use strict';

// import de la librairie barba.js pour la gestion des transitions de pages
// import barba from '@barba/core';

// import de la librairie gsap pour les animations
import { gsap } from "gsap";
import { ScrollTrigger } from "gsap/ScrollTrigger";
gsap.registerPlugin(ScrollTrigger);

import { updateFontSize } from './updateFontSize.js';

window.addEventListener('DOMContentLoaded', () => {
    updateFontSize();
    window.addEventListener('resize', updateFontSize); 
});

import { navbar } from './navbar.js';
import { registerForm } from './register.js';
import { loginForm } from './login.js';
import { search } from './search.js';
import { list } from "./list.js";
import { movie } from "./movie.js";
import { profil } from "./profil.js";
import { forgotPassword } from "./forgotPassword.js";
import { newPassword } from "./newPassword.js";
import { accueil } from "./accueil.js";

const section = document.querySelector('section');

if (section && section.classList.contains('login')) {
   
    loginForm();
} else if (section && section.classList.contains('register')) {
   
    registerForm();
} else if (section && section.classList.contains('forgot-password')) {
   
    forgotPassword();
} else if (section && section.classList.contains('new-password')) {
   
    newPassword();
} else if (section && section.classList.contains('accueil')) {
    
    navbar();
    accueil();
} else if (section && section.classList.contains('search')) {
    
    navbar();
    search();
} else if (section && section.classList.contains('lists')) {
    
    navbar();
} else if (section && section.classList.contains('list')) {
    
    navbar();
    list();
} else if (section && section.classList.contains('movie')) {
    
    navbar();
    movie();
} else if (section && section.classList.contains('profil')) {
    
    navbar();
    profil();
} else if (section && section.classList.contains('credits')) {
    
    navbar();
};