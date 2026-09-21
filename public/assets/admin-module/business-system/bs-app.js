(function () {
  "use strict";
  const root = document.getElementById("bs-explorer");
  if (!root) return;
  const byId = (id) => root.querySelector("#" + id);
  const V = root.getAttribute("data-version") || "bs1";
  const art = (file) => (root.getAttribute("data-art-base") || "").replace(/\/$/, "") + "/" + file + "?v=" + V;
  const esc = (s) => String(s).replace(/[&<>"']/g, (c) => ({
    "&": "&amp;", "<": "&lt;", ">": "&gt;", '"': "&quot;", "'": "&#39;"
  }[c]));

  const TREE = {
    id: "ceo",
    name: "CEO",
    line: "Sets the company direction and holds the four heads accountable. Makes the final call on priorities, hiring of heads, and what Panun Kaergar will and will not do.",
    iconFile: "control",
    scene: "control",
    reports: [
      {
        id: "hog",
        name: "Head of Growth",
        line: "Owns demand and market growth. Turns brand, field work and research into a steady pipeline of the right customers.",
        iconFile: "growth",
        scene: "growth",
        tint: "g-growth",
        reports: [
          { id: "hom", name: "Head of Marketing", line: "Owns marketing campaigns, content and brand. Makes sure every message is clear, consistent and brings the right people in.", iconFile: "mkt", scene: "mkt" },
          { id: "fmm", name: "Field Marketing Manager", line: "Owns on-ground marketing in towns and neighbourhoods. Runs stalls, local presence and face-to-face demand work.", iconFile: "me", scene: "me" },
          { id: "bdm", name: "Business Development & Market Research Manager", line: "Owns business development and market research. Finds new demand, studies the market and opens the next growth path.", iconFile: "mi", scene: "mi" }
        ]
      },
      {
        id: "hoo",
        name: "Head of Operations",
        line: "Owns fulfilment and day-to-day operations. Makes sure bookings are completed well and the customer never has to chase the work.",
        iconFile: "operations",
        scene: "operations",
        tint: "g-operations",
        reports: [
          {
            id: "cxm",
            name: "Customer Experience Manager",
            line: "Owns the customer journey from first contact to a completed booking. Coaches executives and keeps response and conversion on track.",
            iconFile: "cx",
            scene: "cx",
            reports: [
              { id: "cxe", name: "Customer Experience Executive", line: "Handles leads, follow-ups and bookings every day. Keeps the customer informed and closes work to a clear outcome.", iconFile: "cx", scene: "cx" }
            ]
          },
          {
            id: "pom",
            name: "Provider Operations Manager",
            line: "Owns provider fulfilment. Assigns jobs, watches quality on the ground and makes sure the provider side of every booking is done.",
            iconFile: "po",
            scene: "po",
            reports: [
              { id: "poe", name: "Provider Operations Executives", line: "Coordinate providers day to day. Confirm job start, track completion and raise issues before the customer feels them.", iconFile: "po", scene: "po" }
            ]
          }
        ]
      },
      {
        id: "hof",
        name: "Head of Finance & Administration",
        line: "Owns money, people operations and administration. Keeps accounts clean and the office running so the rest of the company can work.",
        iconFile: "fpc",
        scene: "fpc",
        tint: "g-control",
        reports: [
          {
            id: "fam",
            name: "Finance & Accounts Manager",
            line: "Owns finance and accounts. Tracks money in and out, reviews books, and flags anything that is late, missing or off policy.",
            iconFile: "fpc",
            scene: "fpc",
            reports: [
              { id: "ace", name: "Accounts Executive", line: "Posts day-to-day accounts, invoices and receipts. Keeps records complete so the manager can close the books.", iconFile: "fpc", scene: "fpc" }
            ]
          },
          {
            id: "hrm",
            name: "HR & Administration Manager",
            line: "Owns people and office administration. Hiring, attendance, files and the basics that keep the team able to work.",
            iconFile: "hr",
            scene: "hr",
            reports: [
              { id: "hre", name: "HR & Administration Executive", line: "Runs daily HR and admin tasks. Onboarding, records, office support and follow-through on people requests.", iconFile: "hr", scene: "hr" }
            ]
          }
        ]
      },
      {
        id: "hot",
        name: "Head of Technology & Data",
        line: "Owns systems, product and data. Makes sure the tools the company runs on are reliable, secure and actually used.",
        iconFile: "td",
        scene: "td",
        tint: "g-technology",
        reports: [
          {
            id: "set",
            name: "Software Engineer Team Lead",
            line: "Leads the engineering team that builds and maintains the product. Breaks work down, reviews quality and ships what operations need.",
            iconFile: "td",
            scene: "td",
            reports: [
              { id: "swe", name: "Software Engineers", line: "Write, test and fix the product every day. Turn tickets into working features and keep existing flows stable.", iconFile: "td", scene: "td" }
            ]
          }
        ]
      }
    ]
  };

  const COLLAPSE_KEY = "pk-org-collapsed-v2";
  function loadCollapsed() {
    try {
      const raw = JSON.parse(localStorage.getItem(COLLAPSE_KEY) || "[]");
      return new Set(Array.isArray(raw) ? raw : []);
    } catch (e) {
      return new Set();
    }
  }
  const collapsed = loadCollapsed();
  let selected = null;
  function saveCollapsed() {
    localStorage.setItem(COLLAPSE_KEY, JSON.stringify(Array.from(collapsed)));
  }
  function isCollapsed(id) {
    return collapsed.has(id);
  }
  const SEATS = {};
  (function indexTree(node, parentId) {
    SEATS[node.id] = Object.assign({}, node, { reportsTo: parentId || null });
    (node.reports || []).forEach((child) => indexTree(child, node.id));
  })(TREE, null);

  const ART = {
    ceo: "org-ceo.png",
    hog: "org-growth.png",
    hom: "org-marketing.png",
    fmm: "org-field.png",
    bdm: "org-research.png",
    hoo: "org-operations.png",
    cxm: "org-customer.png",
    cxe: "org-cx-exec.png",
    pom: "org-provider.png",
    poe: "org-provider-exec.png",
    hof: "org-finance-head.png",
    fam: "org-accounts.png",
    ace: "org-accounts-exec.png",
    hrm: "org-hr.png",
    hre: "org-hr-exec.png",
    hot: "org-technology.png",
    set: "org-engineering.png",
    swe: "org-engineers.png"
  };
  function sceneFor(node) {
    return art(ART[node.id] || "org-ceo.png");
  }
  function renderAssignee(node) {
    const person = node.assignee;
    if (person && person.name) {
      return `<div class="org-assignee">
        <img class="org-assignee-photo" src="${esc(person.photo || "")}" alt="">
        <div class="org-assignee-copy">
          <strong>${esc(person.name)}</strong>
          <em>${esc(person.email || "")}</em>
        </div>
      </div>`;
    }
    return `<div class="org-assignee is-empty">
      <div class="org-assignee-photo" aria-hidden="true"><span class="mso">person</span></div>
      <div class="org-assignee-copy">
        <strong>To be filled</strong>
        <em>email</em>
      </div>
    </div>`;
  }
  function renderBox(node) {
    const tint = node.tint ? " " + node.tint : "";
    const rootClass = node.id === "ceo" ? " is-root" : "";
    const selectedClass = selected === node.id ? " is-selected" : "";
    return `<div class="node org-box${tint}${rootClass}${selectedClass}" id="org-node-${node.id}" data-select="${node.id}" role="button" tabindex="0">
      <span class="org-card-art-wrap"><img class="org-card-art" src="${sceneFor(node)}" alt=""></span>
      <b class="serif">${esc(node.name)}</b>
      <span class="org-card-desc">${esc(node.line || "")}</span>
      ${renderAssignee(node)}
    </div>`;
  }

  function renderToggle(node, kidCount) {
    if (!kidCount) return "";
    const closed = isCollapsed(node.id);
    return `<button type="button" class="org-toggle" data-toggle="${node.id}" aria-expanded="${closed ? "false" : "true"}" aria-label="${closed ? "Expand" : "Collapse"} ${esc(node.name)}">
      <span class="mso">${closed ? "add" : "remove"}</span>
    </button>`;
  }

  function renderBranch(node) {
    const kids = node.reports || [];
    const box = renderBox(node);
    const closed = kids.length ? isCollapsed(node.id) : false;
    let childrenHtml = "";
    if (kids.length === 1) {
      childrenHtml = `<div class="vline"></div>${renderBranch(kids[0])}`;
    } else if (kids.length > 1) {
      childrenHtml = `<div class="vline is-stem"></div>
        <div class="org-kids org-n-${kids.length}">
          ${kids.map((child) => renderBranch(child)).join("")}
        </div>`;
    }
    return `<div class="branch${closed ? " is-collapsed" : ""}${kids.length ? "" : " org-leaf"}" data-branch="${node.id}">
      <div class="org-card-wrap">
        ${box}
        ${renderToggle(node, kids.length)}
      </div>
      ${childrenHtml ? `<div class="org-children"><div class="org-children-inner">${childrenHtml}</div></div>` : ""}
    </div>`;
  }

  function renderOverview() {
    return `
      <div class="page page-arch org-page">
        <div class="org-zoom-bar" role="toolbar" aria-label="Tree zoom">
          <button type="button" class="org-zoom-btn" data-zoom-out aria-label="Zoom out"><span class="mso">remove</span></button>
          <span class="org-zoom-label" data-zoom-label>100%</span>
          <button type="button" class="org-zoom-btn" data-zoom-in aria-label="Zoom in"><span class="mso">add</span></button>
          <button type="button" class="org-zoom-fit" data-zoom-fit>Fit tree</button>
        </div>
        <div class="arch-scroll">
          <div class="org-zoom-stage">
            <div class="org-zoom-inner">
              <div class="arch org-tree">${renderBranch(TREE)}</div>
            </div>
          </div>
        </div>
      </div>
    `;
  }

  let zoom = 1;
  let pinFit = true;
  const ZOOM_MAX = 1.8;

  function scrollerEl() {
    return byId("bs-main").querySelector(".arch-scroll");
  }
  function stageEl() {
    return byId("bs-main").querySelector(".org-zoom-stage");
  }
  function innerEl() {
    return byId("bs-main").querySelector(".org-zoom-inner");
  }
  function treeEl() {
    return byId("bs-main").querySelector(".org-tree");
  }
  function nativeTreeSize() {
    const tree = treeEl();
    if (!tree) return { w: 1, h: 1 };
    return {
      w: Math.max(1, tree.offsetWidth),
      h: Math.max(1, tree.offsetHeight)
    };
  }
  function fitScale() {
    const scroller = scrollerEl();
    const tree = treeEl();
    if (!scroller || !tree) return 1;
    const { w, h } = nativeTreeSize();
    const sx = (scroller.clientWidth - 48) / w;
    const sy = (scroller.clientHeight - 72) / h;
    return Math.max(0.08, Math.min(1, sx, sy));
  }
  function minScale() {
    return Math.max(0.08, fitScale() * 0.9);
  }
  function applyZoom() {
    const inner = innerEl();
    const stage = stageEl();
    const scroller = scrollerEl();
    const tree = treeEl();
    if (!inner || !stage || !scroller || !tree) return;
    zoom = Math.min(ZOOM_MAX, Math.max(minScale(), zoom));
    const { w, h } = nativeTreeSize();
    inner.style.transform = "scale(" + zoom + ")";
    inner.style.transformOrigin = "0 0";
    inner.style.width = w + "px";
    inner.style.height = h + "px";
    stage.style.width = (w * zoom) + "px";
    stage.style.height = (h * zoom) + "px";
    const label = byId("bs-main").querySelector("[data-zoom-label]");
    if (label) label.textContent = Math.round(zoom * 100) + "%";
    const outBtn = byId("bs-main").querySelector("[data-zoom-out]");
    const inBtn = byId("bs-main").querySelector("[data-zoom-in]");
    if (outBtn) outBtn.disabled = zoom <= minScale() + 0.001;
    if (inBtn) inBtn.disabled = zoom >= ZOOM_MAX - 0.001;
  }
  function zoomTo(next, clientX, clientY) {
    const scroller = scrollerEl();
    if (!scroller) return;
    pinFit = false;
    const prev = zoom;
    const clamped = Math.min(ZOOM_MAX, Math.max(minScale(), next));
    if (typeof clientX === "number" && typeof clientY === "number") {
      const rect = scroller.getBoundingClientRect();
      const x = scroller.scrollLeft + (clientX - rect.left);
      const y = scroller.scrollTop + (clientY - rect.top);
      zoom = clamped;
      applyZoom();
      const ratio = zoom / prev;
      scroller.scrollLeft = x * ratio - (clientX - rect.left);
      scroller.scrollTop = y * ratio - (clientY - rect.top);
      return;
    }
    zoom = clamped;
    applyZoom();
  }
  function centerTree() {
    const scroller = scrollerEl();
    if (!scroller) return;
    scroller.scrollLeft = Math.max(0, (scroller.scrollWidth - scroller.clientWidth) / 2);
    scroller.scrollTop = Math.max(0, (scroller.scrollHeight - scroller.clientHeight) / 2);
  }
  function fitTree() {
    pinFit = true;
    zoom = fitScale();
    applyZoom();
    centerTree();
  }
  function onViewportChange() {
    if (pinFit) fitTree();
    else applyZoom();
  }

  function toggleBranch(id) {
    if (collapsed.has(id)) collapsed.delete(id);
    else collapsed.add(id);
    saveCollapsed();
    const branch = byId("bs-main").querySelector('[data-branch="' + id + '"]');
    if (!branch) return;
    const closed = collapsed.has(id);
    branch.classList.toggle("is-collapsed", closed);
    const btn = branch.querySelector(':scope > .org-card-wrap > .org-toggle');
    if (btn) {
      btn.setAttribute("aria-expanded", closed ? "false" : "true");
      const icon = btn.querySelector(".mso");
      if (icon) icon.textContent = closed ? "add" : "remove";
      const label = (SEATS[id] || {}).name || id;
      btn.setAttribute("aria-label", (closed ? "Expand " : "Collapse ") + label);
    }
    const scroller = byId("bs-main").querySelector(".arch-scroll");
    const card = branch.querySelector(":scope > .org-card-wrap");
    if (scroller && card && !pinFit) {
      requestAnimationFrame(() => {
        const cr = card.getBoundingClientRect();
        const sr = scroller.getBoundingClientRect();
        scroller.scrollTo({
          top: Math.max(0, scroller.scrollTop + (cr.top - sr.top) - Math.max(24, (sr.height - cr.height) / 2)),
          left: Math.max(0, scroller.scrollLeft + (cr.left - sr.left) - Math.max(24, (sr.width - cr.width) / 2)),
          behavior: "smooth"
        });
        applyZoom();
      });
    }
    requestAnimationFrame(onViewportChange);
    setTimeout(onViewportChange, 480);
  }

  function selectCard(id) {
    selected = id;
    byId("bs-main").querySelectorAll(".org-box.is-selected").forEach((el) => el.classList.remove("is-selected"));
    const card = byId("org-node-" + id);
    if (card) card.classList.add("is-selected");
  }

  function bindOverview() {
    byId("bs-main").querySelectorAll("[data-select]").forEach((card) => {
      card.addEventListener("click", () => selectCard(card.dataset.select));
      card.addEventListener("keydown", (event) => {
        if (event.key === "Enter" || event.key === " ") {
          event.preventDefault();
          selectCard(card.dataset.select);
        }
      });
    });
    byId("bs-main").querySelectorAll("[data-toggle]").forEach((btn) => {
      btn.addEventListener("click", (event) => {
        event.preventDefault();
        event.stopPropagation();
        toggleBranch(btn.dataset.toggle);
      });
    });
    const scroller = scrollerEl();
    const outBtn = byId("bs-main").querySelector("[data-zoom-out]");
    const inBtn = byId("bs-main").querySelector("[data-zoom-in]");
    const fitBtn = byId("bs-main").querySelector("[data-zoom-fit]");
    if (outBtn) outBtn.addEventListener("click", () => zoomTo(zoom / 1.18));
    if (inBtn) inBtn.addEventListener("click", () => zoomTo(zoom * 1.18));
    if (fitBtn) fitBtn.addEventListener("click", () => fitTree());
    if (scroller) {
      scroller.addEventListener("wheel", (event) => {
        if (!event.ctrlKey && !event.metaKey) return;
        event.preventDefault();
        const factor = event.deltaY > 0 ? 0.9 : 1.11;
        zoomTo(zoom * factor, event.clientX, event.clientY);
      }, { passive: false });
    }
    pinFit = true;
    fitTree();
    requestAnimationFrame(() => {
      fitTree();
      requestAnimationFrame(fitTree);
    });
    window.removeEventListener("resize", onViewportChange);
    window.addEventListener("resize", onViewportChange);
  }

  function render() {
    if ((location.hash || "").indexOf("role-") === 1) {
      history.replaceState(null, "", location.pathname + location.search);
    }
    byId("bs-main").innerHTML = renderOverview();
    byId("bs-main").scrollTop = 0;
    bindOverview();
  }

  render();
})();
