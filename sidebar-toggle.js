document.addEventListener("DOMContentLoaded", function () {
  const sidebar = document.querySelector(".sidebar");
  const toggle = sidebar && sidebar.querySelector(".sidebar-toggle");
  if (!sidebar || !toggle) {
    return;
  }

  const layout = sidebar.parentElement;
  const icon = toggle.querySelector("i");

  layout.classList.add("sidebar-collapsible");

  toggle.addEventListener("click", function () {
    const collapsed = layout.classList.toggle("sidebar-collapsed");
    const label = collapsed ? "Mostrar menú lateral" : "Ocultar menú lateral";

    toggle.setAttribute("aria-expanded", String(!collapsed));
    toggle.setAttribute("aria-label", label);
    toggle.title = label;
    icon.className = collapsed ? "bi bi-chevron-right" : "bi bi-chevron-left";
  });
});
