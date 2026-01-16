'use strict'

export function updateFontSize() {

  const root = document.documentElement;
  const width = window.innerWidth;

  if (width < 768) {
    root.style.setProperty('--size-p', '16px');
  } else if (width < 1100) {
    root.style.setProperty('--size-p', '18px');
  } else {
    root.style.setProperty('--size-p', '20px');
  }

}