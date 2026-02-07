// GSAP Global Configuration
import { gsap } from "gsap";
import { ScrollTrigger } from "gsap/ScrollTrigger";
import { ScrollToPlugin } from "gsap/ScrollToPlugin";
import { SplitText } from "gsap/SplitText";
import { Flip } from "gsap/Flip";
import { DrawSVGPlugin } from "gsap/DrawSVGPlugin";
import { GSDevTools } from "gsap/GSDevTools";
import { CustomEase } from "gsap/CustomEase";

// Register all plugins once globally
gsap.registerPlugin(
  ScrollTrigger,
  ScrollToPlugin,
  SplitText,
  Flip,
  DrawSVGPlugin,
  GSDevTools,
  CustomEase
);

// Export configured gsap
export {
  gsap,
  ScrollTrigger,
  ScrollToPlugin,
  SplitText,
  Flip,
  DrawSVGPlugin,
  GSDevTools,
  CustomEase,
};

// console.log("GSAP plugins registered globally");
