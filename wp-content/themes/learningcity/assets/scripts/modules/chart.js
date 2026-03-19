import * as echarts from "echarts";
import { SVGRenderer, CanvasRenderer } from "echarts/renderers";

echarts.use([SVGRenderer, CanvasRenderer]);

/**
 * Read chart values injected by WordPress (PHP) via:
 * window.__BLC__ = { chart: { next_jobs, next_skills, other, target } }
 */
function getChartValues() {
  const fallback = {
    next_jobs: 0,
    next_skills: 0,
    other: 0,
    target: 1000000,
  };

  if (typeof window !== "undefined" && window.__BLC__?.chart) {
    const c = window.__BLC__.chart;

    return {
      next_jobs: Number(c.next_jobs ?? fallback.next_jobs),
      next_skills: Number(c.next_skills ?? fallback.next_skills),
      other: Number(c.other ?? fallback.other),
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
  const sum = values.next_jobs + values.next_skills + values.other;

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
            if (name === "Next Jobs") return "{career|} Next Jobs";
            if (name === "Next Skills") return "{it|} Next Skills";
            if (name === "อื่นๆ") return "{lang|} อื่นๆ";
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
          // next jobs
          {
            value: values.next_jobs,
            name: "Next Jobs",
            itemStyle: { color: "#F7DD52" },
          },

          // next skills
          {
            value: values.next_skills,
            name: "Next Skills",
            itemStyle: { color: "#EA3DA9" },
          },

          // other
          {
            value: values.other,
            name: "อื่นๆ",
            itemStyle: { color: "#0972CE" },
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

function safeNumber(value) {
  const normalized = String(value ?? "")
    .replace(/,/g, "")
    .trim();

  const numeric = Number(normalized);
  return Number.isFinite(numeric) ? numeric : 0;
}

function getData() {
  const c = window.__BLC__?.chart || {};
    return {
    next_jobs: Number(c.next_jobs || 0),
    next_skills: Number(c.next_skills || 0),
    other: Number(c.other || 0),
    total: Number(c.total || 0),
    percent: {
      next_jobs: Number(c.percent?.next_jobs || 0),
      next_skills: Number(c.percent?.next_skills || 0),
      other: Number(c.percent?.other || 0),
    },
  };
}

// ✅ เรียกฟังก์ชันนี้หลังหน้าโหลด (DOM พร้อม)
export function renderBarsWithGsap() {
  const d = getData();

  // 1) ใส่เลขรวม
  document.querySelectorAll("#total-hours").forEach((el) => {
    el.textContent = String(d.total);
  });

  // 2) ใส่เลขรายธีม + ตั้ง width เป้าหมาย
  ["next_jobs", "next_skills", "other"].forEach((t) => {
    document.querySelectorAll(`.counter-hours[data-theme="${t}"]`).forEach((el) => {
      el.textContent = String(d[t]);
    });

    document.querySelectorAll(`.progress[data-theme="${t}"]`).forEach((el) => {
      const w = Math.max(0, Math.min(100, safeNumber(d.percent[t])));
      el.style.width = `${w}%`;
    });
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
        safeNumber(value).toLocaleString(undefined, {
          minimumFractionDigits: 0,
          maximumFractionDigits: 0,
        }),
    },
  });
}
