// preload-lazy.js
// === ✅ Force load image fallback with animation
function forceLoadImage(img) {
	const src = img.dataset.src;
	if (!src) return;

	const wrapper = img.closest('.img-wrapper');
	const skeleton = wrapper?.querySelector('.skeleton-bg');

	img.src = src;
	img.classList.add('loaded', 'observed', 'fade-in-img');

	if (skeleton) skeleton.classList.add('fade-out');
}

// === ✅ Force load video fallback with animation
function forceLoadVideo(video) {
	const skeleton = video
		.closest('.video-container')
		?.querySelector('.skeleton-bg');
	const srcTag = video.querySelector('source');

	if (video.readyState === 0 && srcTag) {
		video.load();
	}
	video.play().catch(() => {});
	video.classList.add('observed', 'fade-in-video');

	if (skeleton) skeleton.classList.add('fade-out');
}

// === 👁️ Lazy load IMG ===
window.observeLazyImages = function () {
	const lazyImages = document.querySelectorAll('.lazy-img:not(.observed)');

	const observer = new IntersectionObserver(
		(entries, observer) => {
			entries.forEach((entry) => {
				const img = entry.target;
				if (entry.isIntersecting) {
					forceLoadImage(img);
					observer.unobserve(img);
				}
			});
		},
		{
			rootMargin: '100px 0px',
			threshold: 0.01,
		}
	);

	lazyImages.forEach((img) => {
		const rect = img.getBoundingClientRect();
		const isVisible = rect.top < window.innerHeight && rect.bottom > 0;

		if (isVisible) {
			forceLoadImage(img);
		} else {
			observer.observe(img);
		}

		// ✅ fallback: force load after 5s
		setTimeout(() => {
			if (!img.classList.contains('loaded')) {
				forceLoadImage(img);
				observer.unobserve(img);
			}
		}, 5000);
	});
};

// === 🎬 Lazy load VIDEO ===
window.observeLazyVideos = function () {
	const videos = document.querySelectorAll('.video-lazy:not(.observed)');

	const observer = new IntersectionObserver(
		(entries) => {
			entries.forEach((entry) => {
				const video = entry.target;
				if (entry.isIntersecting) {
					forceLoadVideo(video);
					observer.unobserve(video);
				}
			});
		},
		{
			rootMargin: '200px 0px',
			threshold: 0.25,
		}
	);

	videos.forEach((video) => {
		const rect = video.getBoundingClientRect();
		const isVisible = rect.top < window.innerHeight && rect.bottom > 0;

		if (isVisible) {
			forceLoadVideo(video);
		} else {
			observer.observe(video);
		}

		// ✅ fallback: force load after 5s
		setTimeout(() => {
			if (!video.classList.contains('observed')) {
				forceLoadVideo(video);
				observer.unobserve(video);
			}
		}, 5000);
	});
};

// === 🔁 Init on DOM Ready (wait for render frame)
document.addEventListener('DOMContentLoaded', () => {
	requestAnimationFrame(() => {
		setTimeout(() => {
			window.observeLazyImages();
			window.observeLazyVideos();
		}, 50); // รอ layout เสถียร
	});
});
