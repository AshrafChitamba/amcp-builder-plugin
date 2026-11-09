const toggle = document.querySelector(".menu-toggle");
const nav = document.querySelector("#primary-nav");

toggle?.addEventListener("click", () => {
	const open = toggle.getAttribute("aria-expanded") === "true";
	toggle.setAttribute("aria-expanded", String(!open));
	toggle.setAttribute(
		"aria-label",
		open ? "Open navigation" : "Close navigation",
	);
	nav?.classList.toggle("open", !open);
});

nav?.querySelectorAll("a").forEach((link) => {
	link.addEventListener("click", () => {
		nav.classList.remove("open");
		toggle?.setAttribute("aria-expanded", "false");
		toggle?.setAttribute("aria-label", "Open navigation");
	});
});

if (window.matchMedia("(prefers-reduced-motion: reduce)").matches) {
	document.querySelectorAll(".orbit svg").forEach((svg) => {
		svg.pauseAnimations?.();
	});
}
