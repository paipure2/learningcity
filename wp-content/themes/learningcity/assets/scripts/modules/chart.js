import * as echarts from "echarts";
import { SVGRenderer, CanvasRenderer } from "echarts/renderers";

echarts.use([SVGRenderer, CanvasRenderer]);

/**
 * Read chart values injected by WordPress (PHP) via:
 * window.__BLC__ = { chart: { job, language, digital, target } }
 */
function getChartValues() {
  const fallback = {
    job: 0,
    language: 0,
    digital: 0,
    target: 1000000,
  };

  if (typeof window !== "undefined" && window.__BLC__?.chart) {
    const c = window.__BLC__.chart;

    return {
      job: Number(c.job ?? fallback.job),
      language: Number(c.language ?? fallback.language),
      digital: Number(c.digital ?? fallback.digital),
      target: Number(c.target ?? fallback.target),
    };
  }

  return fallback;
}

/**
 * =========================
 *  PIE CHART (ECharts)
 * =========================
 */
export function chart() {
  const chartDom = document.getElementById("chart");
  if (!chartDom) return;

  const myChart = echarts.init(chartDom, null, { renderer: "svg" });

  window.addEventListener("resize", () => {
    myChart.resize();
  });

  const values = getChartValues();

  // total hours from WP
  const sum = values.job + values.language + values.digital;

  // remaining to reach target (1,000,000)
  const remaining = Math.max(values.target - sum, 0);

  const ICONS = {
    career:
      "https://spicy-dev.com/preview/1dd/bangkok-learning-city/icons/icon_occupation.svg",
    lang:
      "https://spicy-dev.com/preview/1dd/bangkok-learning-city/icons/icon_translate.svg",
    it:
      "https://spicy-dev.com/preview/1dd/bangkok-learning-city/icons/icon_it.svg",
  };

  const option = {
    tooltip: {
      trigger: "item",
    },
    series: [
      {
        type: "pie",
        radius: ["30%", "50%"],
        avoidLabelOverlap: false,
        padAngle: 0,
        itemStyle: {
          borderRadius: 0,
        },
        labelLayout: {
          hideOverlap: false,
        },
        label: {
          show: false,
          position: "inside",
          color: "#000",
          fontSize: 10,
          fontFamily: "Anuphan, system-ui, sans-serif",
          padding: [4, 2, 4, 4],
          borderRadius: 4,
          formatter: ({ name }) => {
            if (name === "อาชีพ") return "{career|} อาชีพ";
            if (name === "ภาษา") return "{lang|} ภาษา";
            if (name === "ไอที") return "{it|} ไอที";
            return "";
          },
          rich: {
            career: {
              width: 14,
              height: 14,
              backgroundColor: {
                image: ICONS.career,
              },
            },
            lang: {
              width: 14,
              height: 14,
              backgroundColor: {
                image: ICONS.lang,
              },
            },
            it: {
              width: 14,
              height: 14,
              backgroundColor: {
                image: ICONS.it,
              },
            },
          },
        },
        labelLine: {
          show: false,
        },

        data: [
          // job
          {
            value: values.job,
            name: "อาชีพ",
            itemStyle: { color: "#F7DD52" },
            // label: {
            //   backgroundColor: "#F7DD52",
            //   color: "#000",
            //   offset: [48, -10],
            // },
          },

          // language
          {
            value: values.language,
            name: "ภาษา",
            itemStyle: { color: "#0972CE" },
            // label: {
            //   backgroundColor: "#0972CE",
            //   color: "#fff",
            //   offset: [50, 25],
            // },
          },

          // digital
          {
            value: values.digital,
            name: "ไอที",
            itemStyle: { color: "#EA3DA9" },
            // label: {
            //   backgroundColor: "#EA3DA9",
            //   color: "#fff",
            //   offset: [-35, -25],
            // },
          },

          // remaining to target 1,000,000
          {
            value: remaining,
            name: "",
            label: { show: false },
            itemStyle: { color: "#005A3A" },
            tooltip: { show: false },
            emphasis: {
              disabled: true,
            },
          },
        ],
      },
    ],
  };

  myChart.setOption(option);
}

/**
 * =========================
 *  ANALOG CLOCK
 * =========================
 */
export function startClock() {
  const hourHand = document.querySelector(".hour-hand");
  const minuteHand = document.querySelector(".minute-hand");
  const secondHand = document.querySelector(".second-hand");

  if (!hourHand || !minuteHand || !secondHand) return;

  function updateClock() {
    const now = new Date();
    const hours = now.getHours() % 12;
    const minutes = now.getMinutes();
    const seconds = now.getSeconds();

    const hourDeg = hours * 30 + minutes * 0.5;
    const minuteDeg = minutes * 6 + seconds * 0.1;
    const secondDeg = seconds * 6;

    hourHand.style.transform = `translateX(-50%) rotate(${hourDeg}deg)`;
    minuteHand.style.transform = `translateX(-50%) rotate(${minuteDeg}deg)`;
    secondHand.style.transform = `translateX(-50%) rotate(${secondDeg}deg)`;
  }

  updateClock();
  setInterval(updateClock, 1000);
}


import gsap from "gsap";

function getData() {
  const c = window.__BLC__?.chart || {};
  return {
    job: Number(c.job || 0),
    language: Number(c.language || 0),
    digital: Number(c.digital || 0),
    total: Number(c.total || 0),
    percent: {
      job: Number(c.percent?.job || 0),
      language: Number(c.percent?.language || 0),
      digital: Number(c.percent?.digital || 0),
    },
  };
}

// ✅ เรียกฟังก์ชันนี้หลังหน้าโหลด (DOM พร้อม)
export function renderBarsWithGsap() {
  const d = getData();

  // 1) ใส่เลขรวม
  const totalEl = document.querySelector("#total-hours");
  if (totalEl) totalEl.textContent = String(d.total);

  // 2) ใส่เลขรายธีม + ตั้ง width เป้าหมาย
  ["job", "digital", "language"].forEach((t) => {
    const hoursEl = document.querySelector(`.counter-hours[data-theme="${t}"]`);
    if (hoursEl) hoursEl.textContent = String(d[t]);

    const barEl = document.querySelector(`.progress[data-theme="${t}"]`);
    if (barEl) {
      const w = Math.max(0, Math.min(100, d.percent[t]));
      barEl.style.width = `${w}%`; // ✅ ปลายทาง
    }
  });

  // 3) Animate (ของคุณเดิมเลย)
  gsap.killTweensOf(".progress");
  gsap.killTweensOf(".counter-hours");

  gsap.from(".progress", {
    duration: 0.8,
    width: 0,
    ease: "back.out",
    stagger: 0.2,
  });

  gsap.from(".counter-hours", {
    duration: 1,
    textContent: 0,
    snap: { textContent: 1 },
    ease: "power4.out",
    modifiers: {
      textContent: (value) =>
        Number(value).toLocaleString(undefined, {
          minimumFractionDigits: 0,
          maximumFractionDigits: 0,
        }),
    },
  });
}
