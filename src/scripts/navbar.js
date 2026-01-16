'use strict';

import { gsap } from "gsap";
import { ScrollTrigger } from "gsap/ScrollTrigger";
gsap.registerPlugin(ScrollTrigger);

export function navbar() {

    const navbar = document.querySelector('.navbar');
    const burgerMenu = document.querySelector('.burger-menu');

    burgerMenu.addEventListener('click', () => {

        navbar.classList.toggle('active');
        document.body.classList.toggle('no-scroll');

    });

}