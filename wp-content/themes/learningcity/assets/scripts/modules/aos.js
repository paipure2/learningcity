import AOS from "aos";
import "aos/dist/aos.css";

export function initAOS() {
  AOS.init({
    duration: 650,
    offset: 150,
    once: true,
  });
}
