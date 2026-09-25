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
  const escapeRe = (s) => String(s).replace(/[.*+?^${}()|[\]\\]/g, "\\$&");
  let glossIndex = [];
  function setGlossary(items) {
    glossIndex = [];
    (items || []).forEach((term) => {
      const aliases = (term.aliases && term.aliases.length ? term.aliases : [term.term]).slice();
      aliases.forEach((alias) => {
        if (!alias) return;
        const start = /^\w/.test(alias) ? "\\b" : "";
        const end = /\w$/.test(alias) ? "\\b" : "";
        glossIndex.push({
          id: term.id,
          alias: alias,
          re: new RegExp(start + escapeRe(alias) + end, "gi")
        });
      });
    });
    glossIndex.sort((a, b) => b.alias.length - a.alias.length);
  }
  function glossLink(raw) {
    const source = String(raw == null ? "" : raw);
    if (!source || !glossIndex.length) return esc(source);
    const matches = [];
    glossIndex.forEach((entry) => {
      entry.re.lastIndex = 0;
      let m;
      while ((m = entry.re.exec(source)) !== null) {
        matches.push({ start: m.index, end: m.index + m[0].length, id: entry.id, text: m[0] });
        if (!m[0].length) entry.re.lastIndex += 1;
      }
    });
    matches.sort((a, b) => a.start - b.start || (b.end - b.start) - (a.end - a.start));
    const kept = [];
    let cursor = 0;
    matches.forEach((m) => {
      if (m.start < cursor) return;
      kept.push(m);
      cursor = m.end;
    });
    if (!kept.length) return esc(source);
    let out = "";
    let i = 0;
    kept.forEach((m) => {
      out += esc(source.slice(i, m.start));
      out += `<span class="rd-term" data-gloss="${esc(m.id)}" role="link" tabindex="0">${esc(m.text)}</span>`;
      i = m.end;
    });
    return out + esc(source.slice(i));
  }

  const TREE = {
    id: "ceo",
    name: "CEO",
    line: "Writes what the company will and will not do. Holds the four heads to one result each. Each week writes the money note from the records: jobs settled on both sides, growth spend against the signed amount, and cash not yet in the books.",
    iconFile: "control",
    scene: "control",
    reports: [
      {
        id: "hog",
        name: "Head of Growth",
        line: "Owns who we sell to, what we may promise, and the numbers. Holds Marketing, Intelligence, Offer, Expansion, and Partnerships to one result each. If a seat is empty, the day’s note names that seat, what was done, and that the other four results were still read. The weekly review still happens.",
        iconFile: "growth",
        scene: "growth",
        tint: "g-growth",
        reports: [
          {
            id: "mkm",
            name: "Marketing Manager",
            line: "Writes the monthly marketing plan Head of Growth signs: paid ads, SEO (search), stalls, first local workers, office and society contracts, money, and the words they may use. Gives Digital and Field their written work and checks the result. Makes sure Operations asked each person where they found us and wrote the answer.",
            iconFile: "mkt",
            scene: "mkt",
            reports: [
              {
                id: "hom",
                name: "Digital Marketing Manager",
                line: "Owns all online work: paid ads, website and app search, social, other sites, videos and posts. Sends digital enquiries to Customer Experience after Operations has written how they found us.",
                iconFile: "mkt",
                scene: "mkt",
                reports: [
                  { id: "cmc", name: "Content Maker", line: "Produces AI videos, AI still posts, brand films, and founder films, plus edited Operations footage, for the Digital Marketing Manager to approve and publish.", iconFile: "mkt", scene: "mkt" }
                ]
              },
              {
                id: "fmm",
                name: "Field Marketing Manager",
                line: "Runs three ground jobs: stall visits, first local workers who sign, and office and society contracts. Do not hire this seat until all three are already happening. Until then Marketing Manager writes: I am doing Field Marketing today because the seat is empty. The box stays.",
                iconFile: "me",
                scene: "me",
                reports: [
                  { id: "fve", name: "Field Visitor", line: "Actually visits the market — stall, street, or follow-up. May be a full-time employee or someone we pay on a written contract. Collects names for Customer Experience the same day. Does not take the booking or money.", iconFile: "me", scene: "me" },
                  { id: "fpo", name: "First Provider Onboarding", line: "Goes in person to the first local workers and gets them to sign the papers. Sends the signed file to the Provider Experience Manager the same day. Does not put them on customer jobs.", iconFile: "me", scene: "me" },
                  { id: "flc", name: "Office and Society Contracts", line: "Writes maintenance contracts with offices and housing societies from the signed papers Offer and Finance already approved. A verbal yes is not a contract.", iconFile: "me", scene: "me" }
                ]
              }
            ]
          },
          { id: "mim", name: "Market Intelligence Manager", line: "Writes demand, competitors, and ideas with a source and a date. Closes each idea as go, no-go, or more research. Does not run ads, write the service, or open a town.", iconFile: "mi", scene: "mi" },
          { id: "osd", name: "Offer & Service Development Manager", line: "Writes what Panun Kaergar can sell and finish: what is in, what is out, how the job should run. Finance prices. Operations says they can do it. Head of Growth says Marketing may talk.", iconFile: "sd", scene: "sd" },
          { id: "mem", name: "Market Expansion Manager", line: "Writes enter, wait, or leave for a named town only after the Provider Experience Manager says the first jobs can be finished. Does not hire workers, and does not market before we can do the work.", iconFile: "me", scene: "me" },
          { id: "pcm", name: "Partnerships & Channels Manager", line: "Signs hotel desks and shops that send us their customers. Sends named leads to Customer Experience the same day. Does not book the customer, and does not write office maintenance contracts.", iconFile: "pc", scene: "pc" }
        ]
      },
      {
        id: "hoo",
        name: "Head of Operations",
        line: "Sees the whole operation. If a manager is on leave and no deputy was named, names one person that morning and does not take the queue.",
        iconFile: "operations",
        scene: "operations",
        tint: "g-operations",
        reports: [
          {
            id: "cxm",
            name: "Customer Experience Manager",
            line: "Assigns every customer and future-customer lead. Hot before warm before cold. Each week reads bookings taken next to those same bookings finished and settled on both sides.",
            iconFile: "cx",
            scene: "cx",
            reports: [
              { id: "cxe", name: "Customer Experience Executive", line: "Works only the leads with your name. Hot before warm before cold. The last message on that lead is from Panun Kaergar.", iconFile: "cx", scene: "cx" }
            ]
          },
          {
            id: "pom",
            name: "Provider Experience Manager",
            line: "Assigns every service provider lead. Onboard now before write down, do later. Names a deputy before leave.",
            iconFile: "po",
            scene: "po",
            reports: [
              { id: "poe", name: "Provider Support", line: "Works only the provider leads with your name. Onboard now before later. Does not change company prices.", iconFile: "po", scene: "po" }
            ]
          }
        ]
      },
      {
        id: "hof",
        name: "Head of Finance and People",
        line: "Checks the books and the people files. Stops an amount that is not in a written policy. Names a deputy if a manager left the name blank.",
        iconFile: "fpc",
        scene: "fpc",
        tint: "g-control",
        reports: [
          {
            id: "fam",
            name: "Finance & Accounts Manager",
            line: "Assigns every open payment. The books match the booking. Does not invent a price.",
            iconFile: "fpc",
            scene: "fpc",
            reports: [
              { id: "ace", name: "Accounts Executive", line: "Writes only the money items with your name. Sends back a blank field. Does not invent an amount.", iconFile: "fpc", scene: "fpc" }
            ]
          },
          {
            id: "hrm",
            name: "HR & Administration Manager",
            line: "Hires only from a written gap, and only when that seat’s own work is already happening. One person, one file. Does not hire Field Marketing until stall visits, first signatures, and office contracts are each already happening.",
            iconFile: "hr",
            scene: "hr",
            reports: [
              { id: "hre", name: "HR & Administration Executive", line: "Files the people tasks with your name. Does not set pay. Another person can continue the file.", iconFile: "hr", scene: "hr" }
            ]
          }
        ]
      },
      {
        id: "hot",
        name: "Head of Technology & Data",
        line: "The tools are up. A change does not hide a booking or a payment. Names a deputy if the team lead left the name blank.",
        iconFile: "td",
        scene: "td",
        tint: "g-technology",
        reports: [
          {
            id: "set",
            name: "Software Engineer Team Lead",
            line: "Gives every ticket to one engineer. A broken seat comes before a new idea. Checks the release.",
            iconFile: "td",
            scene: "td",
            reports: [
              { id: "swe", name: "Software Engineer", line: "Builds only the tickets with your name. Writes what changed. Stops if a live record has no written yes.", iconFile: "td", scene: "td" }
            ]
          }
        ]
      }
    ]
  };

  const COLLAPSE_KEY = "pk-org-collapsed-v3";
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
    ceo: "role-ceo.png",
    hog: "role-hog.png",
    mkm: "role-mkm.png",
    hom: "role-hom.png",
    cmc: "role-cmc.png",
    fmm: "role-fmm.png",
    fve: "role-fve.png",
    fpo: "role-fpo.png",
    flc: "role-flc.png",
    mim: "role-mim.png",
    osd: "role-osd.png",
    mem: "role-mem.png",
    pcm: "role-pcm.png",
    hoo: "role-hoo.png",
    cxm: "role-cxm.png",
    cxe: "role-cxe.png",
    pom: "role-pom.png",
    poe: "role-poe.png",
    hof: "role-hof.png",
    fam: "role-fam.png",
    ace: "role-ace.png",
    hrm: "role-hrm.png",
    hre: "role-hre.png",
    hot: "role-hot.png",
    set: "role-set.png",
    swe: "role-swe.png"
  };
  function sceneFor(node) {
    return art(ART[node.id] || "org-ceo.png");
  }

  const G = window.PK_GROWTH || { roleIds: [], roles: {}, workflows: {}, workflowTabs: [] };
  const ROLE_IDS = {};
  (G.roleIds || []).forEach((id) => { ROLE_IDS[id] = true; });

  function reportsToName(id) {
    if (!id) return "Board / owners";
    return (SEATS[id] && SEATS[id].name) || id;
  }

  function hashOf() {
    return (location.hash || "").replace(/^#/, "");
  }

  function goHome() {
    if (location.hash) history.pushState(null, "", location.pathname + location.search);
    render();
  }

  function goHash(hash) {
    const clean = String(hash || "").replace(/^#/, "");
    if (hashOf() === clean) render();
    else location.hash = clean;
  }

  function listHtml(items, cls) {
    return `<ul class="${cls || "rd-list"}">${(items || []).map((item) => {
      if (item && typeof item === "object") {
        const title = item.title || item.item || item.field || "";
        const why = item.why || item.detail || "";
        return `<li>${why ? `<strong>${glossLink(title)}</strong><span class="rd-list-why">${glossLink(why)}</span>` : `<span class="rd-list-line">${glossLink(title)}</span>`}</li>`;
      }
      return `<li><span class="rd-list-line">${glossLink(item)}</span></li>`;
    }).join("")}</ul>`;
  }

  function secHead(file, kicker, title, lede) {
    const artHtml = file
      ? `<div class="rd-sec-art"><img src="${art(file)}" alt=""></div>`
      : "";
    return `<div class="rd-sec-band${file ? "" : " is-plain"}">
      ${artHtml}
      <div class="rd-sec-copy">
        ${kicker ? `<p class="rd-kicker">${esc(kicker)}</p>` : ""}
        <h2 class="serif">${esc(title)}</h2>
        ${lede ? `<p class="rd-lede">${glossLink(lede)}</p>` : ""}
      </div>
    </div>`;
  }

  function renderReportPacks(packs) {
    return (packs || []).map((rpt) => `
      <article class="rd-report">
        <div class="rd-report-top">
          <p class="rd-sop-id">${esc(rpt.id)}</p>
          <h3>${esc(rpt.name)}</h3>
          <p class="rd-when-line"><b>When.</b> ${esc(rpt.when)}</p>
        </div>
        <div class="rd-report-facts">
          <div>
            <h4>Objective</h4>
            <p>${esc(rpt.objective)}</p>
          </div>
          <div>
            <h4>Who makes it</h4>
            <p>${esc(rpt.owner)}</p>
          </div>
        </div>
        <h4>What to collect — and why</h4>
        ${listHtml(rpt.collect, "rd-list is-check")}
        <h4>Data that must be on the page</h4>
        <ul class="rd-chips">${(rpt.mustHave || []).map((item) => `<li>${esc(item)}</li>`).join("")}</ul>
        <h4>Who to pass it to — and why</h4>
        <ul class="rd-list">${(rpt.passTo || []).map((item) => `<li><strong>${esc(item.who)}</strong><span>${esc(item.why)}</span></li>`).join("")}</ul>
      </article>
    `).join("");
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
    const hasRole = ROLE_IDS[node.id] ? " has-role" : "";
    const view = ROLE_IDS[node.id]
      ? `<button type="button" class="org-view-role" data-open-role="${node.id}"><span class="mso">menu_book</span> View role</button>`
      : "";
    return `<div class="node org-box${tint}${rootClass}${selectedClass}${hasRole}" id="org-node-${node.id}" data-select="${node.id}" role="button" tabindex="0">
      <span class="org-card-art-wrap">
        <img class="org-card-art" src="${sceneFor(node)}" alt="">
        ${view}
      </span>
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

  function systemTabs(active) {
    const key = String(active || "");
    const filesOn = key === "files" || key.indexOf("file-") === 0;
    const reportsOn = key === "reports" || key.indexOf("report-") === 0;
    return `<nav class="bs-system-tabs" role="tablist" aria-label="Business system">
      <button type="button" class="bs-system-tab${filesOn || reportsOn ? "" : " is-on"}" data-go-home>Hierarchy</button>
      <button type="button" class="bs-system-tab${filesOn ? " is-on" : ""}" data-go-hash="files">Files</button>
      <button type="button" class="bs-system-tab${reportsOn ? " is-on" : ""}" data-go-hash="reports">Reports</button>
    </nav>`;
  }

  function renderOverview() {
    return `
      <div class="page page-arch org-page">
        ${systemTabs("")}
        <div class="org-zoom-bar" role="toolbar" aria-label="Tree zoom">
          <button type="button" class="org-zoom-fit" data-open-workflows>Workflows</button>
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

  const ROLE_JUMPS = [
    ["rd-role", "The role"],
    ["rd-lanes", "Three parts"],
    ["rd-owns", "Owns"],
    ["rd-given", "Given"],
    ["rd-graph", "Connects"],
    ["rd-do", "What you do"],
    ["rd-hand", "Handoffs"],
    ["rd-when", "When"],
    ["rd-how", "How"],
    ["rd-rpt", "Reports"],
    ["rd-std", "Standards"],
    ["rd-kpi", "KPIs"],
    ["rd-look", "Good / bad"],
    ["rd-rec", "Records"],
    ["rd-rules", "Rules"],
    ["rd-esc", "Escalations"]
  ];
  const DETAILED_JUMPS = [
    ["rd-role", "What this job is"],
    ["rd-def", "What / Why"],
    ["rd-owns", "Owns"],
    ["rd-lanes", "Three parts"],
    ["rd-do", "Responsibilities"],
    ["rd-hand", "Handoffs"],
    ["rd-how", "Work flow"],
    ["rd-rpt", "Reporting"],
    ["rd-std", "Standards"],
    ["rd-look", "Good / bad"],
    ["rd-kpi", "KPIs"],
    ["rd-esc", "Escalations"],
    ["rd-gloss", "Glossary"]
  ];

  function jumpNav(role) {
    const jumps = (role && role.layout === "detailed" ? DETAILED_JUMPS : ROLE_JUMPS).filter(([id]) => {
      if (id === "rd-lanes") return !!(role && role.lanes && role.lanes.length);
      if (id === "rd-given") return !!(role && ((role.given && role.given.length) || (role.sentBack && role.sentBack.length)));
      if (id === "rd-graph") return !!(role && role.graph);
      if (id === "rd-owns") return !!(role && ((role.owns && role.owns.length) || (role.mustNot && role.mustNot.length)));
      if (id === "rd-def") {
        const def = role && role.detailed && role.detailed.definition;
        return !!(def && ((def.what && def.what.length) || (def.why && def.why.length)));
      }
      if (id === "rd-look") return !!(role && ((role.good && role.good.length) || (role.bad && role.bad.length) || (role.records && role.records.length) || (role.rules && role.rules.length)));
      if (id === "rd-gloss") return !!(role && role.detailed && role.detailed.glossary && role.detailed.glossary.length);
      return true;
    });
    return `<nav class="rd-jumps" aria-label="On this page">${jumps.map(([id, label], index) =>
      `<button type="button" class="rd-jump${index === 0 ? " is-on" : ""}" data-jump="${id}">${esc(label)}</button>`
    ).join("")}</nav>`;
  }

  function renderLanes(lanes) {
    return `<div class="rd-lanes">${(lanes || []).map((lane) => `
      <article class="rd-duty rd-lane-card">
        ${lane.art ? `<div class="rd-lane-art"><img src="${art(lane.art)}" alt=""></div>` : ""}
        <div class="rd-lane-copy">
          <h3>${esc(lane.title)}</h3>
          <p><b>What you do.</b> ${esc(lane.work)}</p>
          <p><b>When this part is done.</b> ${esc(lane.result)}</p>
          ${lane.who ? `<p><b>Who does it.</b> ${esc(lane.who)}</p>` : ""}
        </div>
      </article>
    `).join("")}</div>`;
  }

  function renderGivenSent(given, sent) {
    const col = (title, items) => `
      <div>
        <h3>${esc(title)}</h3>
        ${listHtml(items)}
      </div>`;
    return `<div class="rd-split">
      ${given && given.length ? col("What you are given", given) : ""}
      ${sent && sent.length ? col("What you send back", sent) : ""}
    </div>`;
  }

  function renderGraph(graph) {
    if (!graph) return "";
    const col = (title, items, key) => `
      <div class="rd-flow-col">
        <h3>${esc(title)}</h3>
        ${(items || []).map((item) => `
          <article>
            <span class="rd-from">${esc(item[key] || "")}</span>
            <p><strong>${esc(item.what || "")}</strong>${item.rule ? `<span>${esc(item.rule)}</span>` : ""}</p>
          </article>
        `).join("")}
      </div>`;
    return `<div class="rd-flow">
      <div class="rd-flow-hub">
        <span class="mso">inventory_2</span>
        <div>
          <b>${esc(graph.hub || "Library")}</b>
          <p>${esc(graph.hubWhy || "")}</p>
        </div>
      </div>
      <div class="rd-flow-pair">
        ${col(graph.fromTitle || "Where it comes from", graph.from, "from")}
        ${col(graph.toTitle || "Where it goes", graph.to, "to")}
      </div>
    </div>
    ${graph.note ? `<p class="rd-lede rd-flow-note">${esc(graph.note)}</p>` : ""}`;
  }

  function chartCardInner(item) {
    return `${item.art ? `<span class="rd-chart-art"><img src="${art(item.art)}" alt=""></span>` : ""}
      <span class="rd-chart-num">${esc(item.kicker || "")}</span>
      <strong>${glossLink(item.title)}</strong>
      ${item.lead ? `<span class="rd-chart-lead">${glossLink(item.lead)}</span>` : ""}`;
  }
  function renderStepChart(items) {
    if (!items || !items.length) return "";
    const linear = items.filter((item) => !item.fork);
    const forks = items.filter((item) => item.fork);
    const cols = 3;
    const parts = [];
    linear.forEach((item, index) => {
      const col = index % cols;
      if (index && col === 0) {
        parts.push(`<li class="rd-chart-break" aria-hidden="true"><span class="mso">south</span></li>`);
      }
      if (col !== 0) {
        parts.push(`<li class="rd-chart-join" aria-hidden="true"><span class="mso">arrow_forward</span></li>`);
      }
      parts.push(`<li class="rd-chart-card">${chartCardInner(item)}</li>`);
    });
    if (forks.length) {
      parts.push(`<li class="rd-chart-break is-fork" aria-hidden="true"><span class="mso">south</span></li>`);
      parts.push(`<li class="rd-chart-fork">${forks.map((item, index) => `
        ${index ? `<div class="rd-chart-join rd-chart-or" aria-hidden="true"><span>or</span></div>` : ""}
        <article class="rd-chart-card ${item.fork === "no" ? "is-no" : "is-yes"}">
          ${chartCardInner(item)}
        </article>`).join("")}</li>`);
    }
    return `<div class="rd-chart"><ol class="rd-chart-steps" aria-label="Work flow">${parts.join("")}</ol></div>`;
  }

  function renderHandoffChart(incoming, outgoing, role) {
    const hub = (role && role.detailed && role.detailed.hub) || {};
    const box = (item) => `<article>
      <p><strong>${glossLink(item.title)}</strong>${item.lead ? `<span>${glossLink(item.lead)}</span>` : ""}</p>
    </article>`;
    return `<div class="rd-chart rd-chart-io" aria-label="What you receive and what you give">
      <div class="rd-chart-col">
        <p class="rd-kicker">You receive</p>
        ${(incoming || []).map(box).join("")}
      </div>
      <div class="rd-chart-mid">
        <div class="rd-chart-join" aria-hidden="true"><span class="mso">arrow_forward</span></div>
        <div class="rd-chart-hub">
          <span class="mso">${esc(hub.icon || "account_tree")}</span>
          <b>${glossLink((role && role.name) || "This seat")}</b>
          <p>${glossLink(hub.line || "Work in as a pack. Work out as a pack.")}</p>
        </div>
        <div class="rd-chart-join" aria-hidden="true"><span class="mso">arrow_forward</span></div>
      </div>
      <div class="rd-chart-col">
        <p class="rd-kicker">You give</p>
        ${(outgoing || []).map(box).join("")}
      </div>
    </div>`;
  }

  function renderParas(items) {
    return (items || []).map((p) => `<p>${glossLink(p)}</p>`).join("");
  }
  function renderMeta(rows) {
    if (!rows || !rows.length) return "";
    return `<dl class="rd-meta">${rows.map((row) => `<div><dt>${esc(row[0])}</dt><dd>${glossLink(row[1])}</dd></div>`).join("")}</dl>`;
  }
  function renderDetailCard(item) {
    return `<article class="rd-detail">
      <div class="rd-detail-copy">
        ${item.art ? `<div class="rd-detail-art"><img src="${art(item.art)}" alt=""></div>` : ""}
        ${item.kicker ? `<p class="rd-kicker">${esc(item.kicker)}</p>` : ""}
        <h3>${glossLink(item.title)}</h3>
        ${item.lead ? `<p class="rd-detail-lead">${glossLink(item.lead)}</p>` : ""}
        ${renderParas(item.body)}
        ${item.points && item.points.length ? listHtml(item.points) : ""}
        ${renderMeta(item.meta)}
      </div>
    </article>`;
  }
  function renderDetailList(items) {
    return `<div class="rd-details">${(items || []).map(renderDetailCard).join("")}</div>`;
  }
  function renderReportCard(item) {
    return `<article class="rd-detail">
      <div class="rd-detail-copy">
        ${item.art ? `<div class="rd-detail-art"><img src="${art(item.art)}" alt=""></div>` : ""}
        <p class="rd-kicker">${esc(item.period)}${item.kicker ? " · " + esc(item.kicker) : ""}</p>
        <h3>${glossLink(item.title)}</h3>
        ${item.when ? `<p class="rd-when-line"><b>When.</b> ${glossLink(item.when)}</p>` : ""}
        ${item.lead ? `<p class="rd-detail-lead">${glossLink(item.lead)}</p>` : ""}
        ${renderParas(item.body)}
        <h4>What the report must contain</h4>
        ${listHtml(item.contents)}
        ${item.mustHave && item.mustHave.length ? `<h4>Fields that must be on the page</h4>${listHtml(item.mustHave)}` : ""}
        <h4>Who receives it</h4>
        ${listHtml((item.submit || []).map((row) => ({ title: row.to, why: row.why })))}
      </div>
    </article>`;
  }
  function renderKpiCards(items) {
    return `<div class="rd-kpi-cards">${(items || []).map((item) => `
      <article class="rd-kpi-card">
        <p class="rd-kicker">Measure</p>
        <h3>${glossLink(item.name)}</h3>
        <p class="rd-kpi-target"><b>Target.</b> ${glossLink(item.target)}</p>
        <p><b>Why it matters.</b> ${glossLink(item.why)}</p>
        ${item.how ? `<p><b>How it is counted.</b> ${glossLink(item.how)}</p>` : ""}
      </article>
    `).join("")}</div>`;
  }
  function renderKpiTable(items) {
    if (!items || !items.length) return "";
    const hasHow = items.some((item) => item.how);
    return `<div class="rd-table-wrap">
      <table class="rd-table">
        <thead>
          <tr>
            <th>Measure</th>
            <th>Target</th>
            <th>Why it matters</th>
            ${hasHow ? "<th>How it is counted</th>" : ""}
          </tr>
        </thead>
        <tbody>${items.map((item) => `<tr>
          <td>${glossLink(item.name)}</td>
          <td>${glossLink(item.target)}</td>
          <td>${glossLink(item.why || "")}</td>
          ${hasHow ? `<td>${glossLink(item.how || "")}</td>` : ""}
        </tr>`).join("")}</tbody>
      </table>
    </div>`;
  }
  function renderStdGrid(items) {
    if (!items || !items.length) return "";
    const tiles = items.map((item, index) => `
      <button type="button" class="rd-std-tile" data-std="${index}">
        ${item.art ? `<span class="rd-std-tile-art"><img src="${art(item.art)}" alt=""></span>` : ""}
        <span class="rd-std-tile-copy">
          <strong>${glossLink(item.title)}</strong>
          ${item.lead ? `<span>${glossLink(item.lead)}</span>` : ""}
          <em>View standard</em>
        </span>
      </button>`).join("");
    const hidden = items.map((item, index) => `<div id="rd-std-full-${index}" hidden>${renderDetailCard(item)}</div>`).join("");
    return `<div class="rd-std-grid">${tiles}</div>
      ${hidden}
      <div class="rd-modal" hidden>
        <div class="rd-modal-scrim" data-std-close></div>
        <div class="rd-modal-panel" role="dialog" aria-label="Standard">
          <div class="rd-modal-bar">
            <p class="rd-kicker">Standard</p>
            <button type="button" class="rd-modal-close" data-std-close aria-label="Close"><span class="mso">close</span></button>
          </div>
          <div class="rd-modal-body"></div>
        </div>
      </div>`;
  }
  function accordionTitle(item) {
    return item.period ? item.period + " · " + item.title : item.title;
  }
  function renderAccordions(items, openFn) {
    if (!items || !items.length) return "";
    const renderOpen = openFn || renderDetailCard;
    return `<div class="rd-acc">${(items || []).map((item) => `
      <div class="rd-acc-item">
        <div class="rd-acc-head-clip">
          <button type="button" class="rd-acc-head" aria-expanded="false">
            ${item.art ? `<span class="rd-acc-art"><img src="${art(item.art)}" alt=""></span>` : ""}
            <span class="rd-acc-copy">
              ${item.kicker ? `<span class="rd-kicker">${esc(item.kicker)}</span>` : ""}
              <strong>${glossLink(accordionTitle(item))}</strong>
              ${item.lead ? `<span>${glossLink(item.lead)}</span>` : ""}
            </span>
            <span class="mso rd-acc-arrow" aria-hidden="true">expand_more</span>
          </button>
        </div>
        <div class="rd-acc-body">
          <div class="rd-acc-body-inner">
            <button type="button" class="rd-acc-close" aria-label="Close">
              <span class="mso">expand_less</span>
            </button>
            ${renderOpen(item)}
          </div>
        </div>
      </div>`).join("")}</div>`;
  }
  function renderDefCol(title, items) {
    return `<article>
      <h3>${esc(title)}</h3>
      ${listHtml(items)}
    </article>`;
  }

  function renderGlossary(items, artFile) {
    if (!items || !items.length) return "";
    const cards = items.map((term) => `
      <article class="rd-gloss-item" id="gloss-${esc(term.id)}">
        <h3>${esc(term.term)}</h3>
        <p>${glossLink(term.meaning)}</p>
        ${term.also ? `<p class="rd-gloss-also"><b>Also called.</b> ${esc(term.also)}</p>` : ""}
      </article>`).join("");
    return `
      <section class="rd-section" id="rd-gloss">
        ${secHead(artFile || "", artFile ? "The shelf" : "", "Glossary", "Words this playbook uses with a fixed meaning. Click a dotted term anywhere on this page to jump to its definition.")}
        <div class="rd-glossary">${cards}</div>
      </section>`;
  }

  function renderOwnsMustNot(role, artFile) {
    const owns = role.owns || [];
    const mustNot = role.mustNot || [];
    if (!owns.length && !mustNot.length) return "";
    const lede = (role.detailed && role.detailed.copy && role.detailed.copy.owns) ||
      "If you do someone else’s job, nobody owns a result. Do your job. Do not take the next person’s job.";
    return `<section class="rd-section" id="rd-owns">
      ${secHead(artFile || "", artFile ? "Boundaries" : "", "What you own, and what you must not do", lede)}
      <div class="rd-split">
        <div>
          <h3>You own</h3>
          ${listHtml(owns)}
        </div>
        <div class="is-not">
          <h3>You must not</h3>
          ${listHtml(mustNot)}
        </div>
      </div>
      ${role.acting ? `<p class="rd-acting">${glossLink(role.acting)}</p>` : ""}
    </section>`;
  }

  function renderSopStrip(procedures) {
    if (!procedures || !procedures.length) return "";
    return `<div class="rd-sops">${procedures.map((item) => `
      <article class="rd-sop">
        <p class="rd-sop-id">${esc(item.id)}</p>
        <h3>${glossLink(item.title)}</h3>
        ${item.when ? `<p class="rd-when-line"><b>When.</b> ${glossLink(item.when)}</p>` : ""}
      </article>
    `).join("")}</div>`;
  }

  function renderLookShelf(role) {
    const good = listHtml(role.good);
    const bad = listHtml(role.bad);
    const records = (role.records || []).map((item) => `<div class="rd-record">${glossLink(item)}</div>`).join("");
    const rules = role.rules || [];
    if (!good && !bad && !records && !rules.length) return "";
    const pictured = picturedDept(role);
    return `<section class="rd-section" id="rd-look">
      ${secHead(pictured ? "sec-look.png" : "", pictured ? "How you know" : "", "What good looks like — and what bad looks like", "You should know if this job is working without a meeting. These are the files, and the lines you do not cross even when it would be faster.")}
      ${good || bad ? `<div class="rd-looks">
        <article class="is-good"><h3>Good</h3>${good}</article>
        <article class="is-bad"><h3>Bad</h3>${bad}</article>
      </div>` : ""}
      ${records ? `<h3 class="rd-group">Records this role keeps</h3><div class="rd-records">${records}</div>` : ""}
      ${rules.length ? `<h3 class="rd-group">Rules</h3>${listHtml(rules, "rd-list is-rule")}` : ""}
    </section>`;
  }

  function picturedDept(role) {
    const dept = role && role.dept;
    return dept === "operations" || dept === "control" || dept === "technology" || dept === "company";
  }

  function workflowLink(role) {
    const dept = (role && role.dept) || "growth";
    const names = {
      growth: "Growth workflows",
      operations: "Operations workflows",
      control: "Control workflows",
      technology: "Technology workflows"
    };
    if (!names[dept] || !(G.workflows && G.workflows[dept])) return "";
    return `<button type="button" class="rd-back is-gold" data-go-hash="workflow-${dept}"><span class="mso">account_tree</span> ${esc(names[dept])}</button>`;
  }

  function renderDetailedRole(role) {
    const seat = SEATS[role.id] || {};
    const reports = reportsToName(seat.reportsTo || role.reportsTo);
    const d = role.detailed || {};
    const copy = d.copy || {};
    setGlossary(d.glossary);
    const incoming = (d.handoffs || []).filter((item) => item.side === "in");
    const outgoing = (d.handoffs || []).filter((item) => item.side === "out");
    const jobIs = copy.definition || role.what || "";
    const hasWhatWhy = !!(d.definition && ((d.definition.what && d.definition.what.length) || (d.definition.why && d.definition.why.length)));
    const ops = picturedDept(role);
    const head = (file, kicker, title, lede) => secHead(ops ? file : "", ops ? kicker : "", title, lede);
    return `
      <div class="page rd-page is-compact is-detailed">
        <div class="rd-top">
          <div class="rd-top-row">
            <button type="button" class="rd-back" data-go-home><span class="mso">arrow_back</span> Organisation</button>
            ${workflowLink(role)}
          </div>
          ${jumpNav(role)}
        </div>
        <header class="rd-hero" id="rd-role">
          <div class="rd-hero-art"><img src="${art(role.hero)}" alt=""></div>
          <div class="rd-copy">
            <p class="rd-kicker">${esc(copy.defTitle || "What this job is")}</p>
            <h1 class="serif">${glossLink(role.name)}</h1>
            <p class="rd-reports">You report to ${glossLink(reports)}</p>
            ${jobIs ? `<p class="rd-job">${glossLink(jobIs)}</p>` : ""}
          </div>
        </header>
        ${hasWhatWhy ? `<section class="rd-section" id="rd-def">
          <div class="rd-def-stack">
            ${renderDefCol("What this role is", d.definition && d.definition.what)}
            ${renderDefCol("Why this role exists", d.definition && d.definition.why)}
          </div>
        </section>` : ""}
        ${renderOwnsMustNot(role, ops ? "sec-owns.png" : "")}
        ${role.lanes && role.lanes.length ? `<section class="rd-section" id="rd-lanes">
          ${head("sec-lanes.png", "Three parts", copy.lanesTitle || "This job has three parts", copy.lanes || "This job has one result. The work is in three parts. Each part says what you do, when it is done, and who does it. If Who is another job and that job is empty, you do that work today. Write that down.")}
          ${renderLanes(role.lanes)}
        </section>` : ""}
        <section class="rd-section" id="rd-do">
          ${head("sec-do.png", "The work", "Responsibilities", copy.responsibilities || "Click a row to open the full card.")}
          ${renderAccordions(d.responsibilities)}
        </section>
        <section class="rd-section" id="rd-hand">
          ${head("sec-hand.png", "In and out", "Handoffs — what you get, what you give", copy.handoffs || "Work arrives as a written pack and leaves as a written pack. Chat is not a pack.")}
          ${renderHandoffChart(incoming, outgoing, role)}
          <h3 class="rd-group">What you receive</h3>
          ${renderAccordions(incoming)}
          <h3 class="rd-group">What you give</h3>
          ${renderAccordions(outgoing)}
        </section>
        <section class="rd-section" id="rd-how">
          ${head("sec-how.png", "Procedures", "Work flow", copy.workflow || "Follow these steps in order. Click a step to open the full card.")}
          ${(d.workflow && d.workflow.length) ? "" : renderSopStrip(role.procedures)}
          ${renderStepChart(d.workflow)}
          ${renderAccordions(d.workflow)}
        </section>
        <section class="rd-section" id="rd-rpt">
          ${head("sec-rpt.png", "The files", "Reporting — daily, weekly, monthly", copy.reporting || "Packs this seat must write. Click a row to open the full pack.")}
          ${renderAccordions(d.reporting, renderReportCard)}
        </section>
        <section class="rd-section" id="rd-std">
          ${head("sec-std.png", "The bar", "Standards", copy.standards || "The bar this seat is measured against. Click a card for the full standard.")}
          ${renderStdGrid(d.standards)}
        </section>
        ${renderLookShelf(role)}
        <section class="rd-section" id="rd-kpi">
          ${head("sec-kpi.png", "Proof", "KPIs", copy.kpis || "Numbers that prove the one result. Each measure has a target, a reason, and a counting rule.")}
          ${renderKpiTable(d.kpis && d.kpis.length ? d.kpis : role.kpis)}
        </section>
        <section class="rd-section" id="rd-esc">
          ${head("sec-esc.png", "When it cannot wait", "Escalations", copy.escalations || "Who gets the problem, how fast, and with what. Do not sit on it.")}
          ${renderAccordions(d.escalations)}
        </section>
        ${renderGlossary(d.glossary, ops ? "sec-rec.png" : "")}
      </div>
    `;
  }

  function renderRole(role) {
    if (role.layout === "detailed") return renderDetailedRole(role);
    const seat = SEATS[role.id] || {};
    const reports = reportsToName(seat.reportsTo || role.reportsTo);
    const duties = (role.responsibilities || []).map((item) => `
      <article class="rd-duty">
        <h3>${esc(item.title)}</h3>
        <p><b>What.</b> ${esc(item.what)}</p>
        ${item.how ? `<p><b>How.</b> ${esc(item.how)}</p>` : ""}
        <p><b>Why.</b> ${esc(item.why)}</p>
      </article>
    `).join("");
    const procedures = (role.procedures || []).map((item) => `
      <article class="rd-sop">
        <p class="rd-sop-id">${esc(item.id)}</p>
        <h3>${esc(item.title)}</h3>
        <p class="rd-when-line"><b>When.</b> ${esc(item.when)}</p>
        <ol>${(item.steps || []).map((step) => `<li>${esc(step)}</li>`).join("")}</ol>
      </article>
    `).join("");
    const kpis = (role.kpis || []).map((item) => `
      <tr>
        <td>${esc(item.name)}</td>
        <td>${esc(item.target)}</td>
        <td>${esc(item.why || "")}</td>
      </tr>
    `).join("");
    const escalations = (role.escalate || []).map((item) => `
      <article class="rd-esc-card">
        <h3>${esc(item.when)}</h3>
        <p><b>To.</b> ${esc(item.to)}</p>
        <p><b>How.</b> ${esc(item.how)}</p>
      </article>
    `).join("");
    const receives = (role.receives || []).map((item) => `
      <article>
        <span class="rd-from">From ${esc(item.from)}</span>
        <p>${esc(item.what)}</p>
      </article>
    `).join("");
    const gives = (role.gives || []).map((item) => `
      <article>
        <span class="rd-from">To ${esc(item.to)}</span>
        <p>${esc(item.what)}</p>
      </article>
    `).join("");
    const records = (role.records || []).map((item) => `<div class="rd-record">${esc(item)}</div>`).join("");
    const good = listHtml(role.good);
    const bad = listHtml(role.bad);
    const reportHtml = renderReportPacks(role.reportPacks);
    return `
      <div class="page rd-page">
        <div class="rd-top">
          <div class="rd-top-row">
            <button type="button" class="rd-back" data-go-home><span class="mso">arrow_back</span> Organisation</button>
            ${workflowLink(role)}
          </div>
          ${jumpNav(role)}
        </div>
        <header class="rd-hero" id="rd-role">
          <div class="rd-hero-art"><img src="${art(role.hero)}" alt=""></div>
          <div class="rd-copy">
            <p class="rd-kicker">Role playbook</p>
            <h1 class="serif">${esc(role.name)}</h1>
            <p class="rd-reports">Reports to ${esc(reports)}</p>
            <p class="rd-result">${esc(role.result)}</p>
            ${role.story ? `<p class="rd-story">${esc(role.story)}</p>` : ""}
          </div>
        </header>
        <section class="rd-section">
          ${secHead("sec-role.png", "The box", "What this role is", "A function, not a person who does a bit of everything. If the box is empty, someone may act in it — and must say so in writing.")}
          <div class="rd-qa">
            <article><h3>What</h3><p>${esc(role.what)}</p></article>
            <article><h3>Why</h3><p>${esc(role.why)}</p></article>
            <article><h3>How</h3><p>${esc(role.how)}</p></article>
          </div>
        </section>
        ${role.lanes && role.lanes.length ? `<section class="rd-section" id="rd-lanes">
          ${secHead("sec-lanes.png", "", "This job has three parts", "This job has one result. The work is in three parts. Each part says what you do, when it is done, and who does it. If Who is another job and that job is empty, you do that work today. Write that down.")}
          ${renderLanes(role.lanes)}
        </section>` : ""}
        ${renderOwnsMustNot(role, "sec-owns.png")}
        ${(role.given || role.sentBack) ? `<section class="rd-section" id="rd-given">
          ${secHead("sec-hand.png", "In and out", "What you are given — and what you send back", "Work arrives as a pack. Work leaves as a pack. Chat is not a pack.")}
          ${renderGivenSent(role.given, role.sentBack)}
        </section>` : ""}
        ${role.graph ? `<section class="rd-section" id="rd-graph">
          ${secHead("sec-graph.png", "The map", "How work connects", "Where videos and images are born, where they are filed, and where they go live.")}
          ${renderGraph(role.graph)}
        </section>` : ""}
        <section class="rd-section" id="rd-do">
          ${secHead("sec-do.png", "The work", "What you do", "What, how, and why for each piece of work. This is the job of the box — not the job of whoever happens to sit in it.")}
          <div class="rd-duties">${duties}</div>
        </section>
        ${receives || gives ? `<section class="rd-section" id="rd-hand">
          ${secHead("sec-hand.png", "In and out", "Handoffs — what you get, what you give", "Work arrives as a pack. Work leaves as a pack. Chat is not a pack.")}
          <div class="rd-split">
            <div>
              <h3>You receive</h3>
              <div class="rd-stack">${receives}</div>
            </div>
            <div>
              <h3>You give</h3>
              <div class="rd-stack">${gives}</div>
            </div>
          </div>
        </section>` : ""}
        <section class="rd-section" id="rd-when">
          ${secHead("sec-when.png", "Rhythm", "When you do it", "Same time every day, week, and month — so the system does not depend on mood.")}
          <div class="rd-when">
            <article><h3>Every working day</h3>${listHtml(role.when && role.when.daily)}</article>
            <article><h3>Every week</h3>${listHtml(role.when && role.when.weekly)}</article>
            <article><h3>Every month</h3>${listHtml(role.when && role.when.monthly)}</article>
          </div>
        </section>
        <section class="rd-section" id="rd-how">
          ${secHead("sec-how.png", "Procedures", "How — step by step", "If it only lives in your head, it is not a procedure. Write it. File it. Teach the next person from the file.")}
          <div class="rd-sops">${procedures}</div>
        </section>
        ${reportHtml ? `<section class="rd-section" id="rd-rpt">
          ${secHead("sec-rpt.png", "The files", "Reports to make", "A report is a decision tool. If it cannot change a yes or a no, do not write it. Collect the fields below, for the reason given, and pass the pack to the named seat.")}
          <div class="rd-report-list">${reportHtml}</div>
        </section>` : ""}
        <section class="rd-section" id="rd-std">
          ${secHead("sec-std.png", "The bar", "Standards", "The line you can be measured against without a meeting.")}
          ${listHtml(role.standards, "rd-list is-check")}
        </section>
        <section class="rd-section" id="rd-kpi">
          ${secHead("sec-kpi.png", "Proof", "KPIs", "Numbers that prove the one result. Reach and likes are not a result.")}
          <div class="rd-table-wrap">
            <table class="rd-table">
              <thead><tr><th>Measure</th><th>Target</th><th>Why it matters</th></tr></thead>
              <tbody>${kpis}</tbody>
            </table>
          </div>
        </section>
        ${good || bad ? `<section class="rd-section" id="rd-look">
          ${secHead("sec-look.png", "How you know", "What good looks like — and what bad looks like", "You should know if this box is working without asking someone.")}
          <div class="rd-looks">
            <article class="is-good"><h3>Good</h3>${good}</article>
            <article class="is-bad"><h3>Bad</h3>${bad}</article>
          </div>
        </section>` : ""}
        ${records ? `<section class="rd-section" id="rd-rec">
          ${secHead("sec-rec.png", "The shelf", "Records this role keeps", "If it is not filed, it did not happen. These are the files the reports above live in.")}
          <div class="rd-records">${records}</div>
        </section>` : ""}
        <section class="rd-section" id="rd-rules">
          ${secHead("sec-rules.png", "Lines", "Rules", "Lines you do not cross even when it would be faster.")}
          ${listHtml(role.rules, "rd-list is-rule")}
        </section>
        <section class="rd-section" id="rd-esc">
          ${secHead("sec-esc.png", "When the box cannot hold it", "Escalations", "Who gets the problem, how fast, and with what. Do not sit on it.")}
          <div class="rd-esc-grid">${escalations}</div>
        </section>
      </div>
    `;
  }

  function fileNo(index) {
    return "PK-0" + (index + 1);
  }

  function renderFileBody(file) {
    const files = window.PK_FILES || [];
    const index = files.findIndex((item) => item.id === file.id);
    const number = fileNo(index < 0 ? 0 : index);
    const lines = (file.current || []).map((item, i) => `
      <div class="bs-sheet-field">
        <span class="bs-sheet-num">${i + 1}</span>
        <div>
          <p class="bs-sheet-label">${esc(item.field)}</p>
          <p class="bs-sheet-line">${esc(item.value)}</p>
          <p class="bs-sheet-hint">${esc(item.note)}</p>
        </div>
      </div>`).join("");
    const must = (file.contains || []).map((item) => `<li><b>${esc(item.title)}.</b> ${esc(item.body)}</li>`).join("");
    const change = (file.change || []).map((step) => `<li>${esc(step)}</li>`).join("");
    return `
      <div class="page bs-files-page">
        ${systemTabs("file-" + file.id)}
        <button type="button" class="rd-back bs-files-back" data-go-hash="files"><span class="mso">arrow_back</span> Cabinet</button>
        <div class="bs-desk">
          <article class="bs-sheet">
            <div class="bs-sheet-holes" aria-hidden="true"><i></i><i></i><i></i></div>
            <div class="bs-sheet-fold" aria-hidden="true"></div>
            <header class="bs-sheet-head">
              <div>
                <p class="bs-sheet-brand">Panun Kaergar</p>
                <p class="bs-sheet-kind">Company file</p>
              </div>
              <div class="bs-sheet-meta">
                <p><span>File</span> ${esc(number)}</p>
                <p><span>Status</span> Blank</p>
                <p><span>Copy</span> Current</p>
              </div>
            </header>
            <h1 class="serif">${esc(file.name)}</h1>
            <p class="bs-sheet-lede">${esc(file.lede)}</p>
            <dl class="bs-sheet-who">
              <div><dt>Written by</dt><dd>${esc(file.owner)}</dd></div>
              <div><dt>Date signed</dt><dd class="is-blank">Not written yet</dd></div>
              <div><dt>Replaces file dated</dt><dd class="is-blank">Not written yet</dd></div>
            </dl>
            <section>
              <h2>What is written on this file today</h2>
              ${lines}
            </section>
            <section>
              <h2>What a finished copy must contain</h2>
              <ol class="bs-sheet-list">${must}</ol>
            </section>
            <section>
              <h2>Who must use this copy</h2>
              <ul class="bs-sheet-list">${(file.uses || []).map((line) => `<li>${esc(line)}</li>`).join("")}</ul>
            </section>
            <section>
              <h2>How this file is replaced</h2>
              <ol class="bs-sheet-list">${change}</ol>
            </section>
            <p class="bs-sheet-stop"><b>If a line is blank.</b> ${esc(file.blank)}</p>
            <footer class="bs-sheet-sign">
              <div>
                <span class="bs-sheet-sign-line"></span>
                <p>${esc(file.owner)}</p>
              </div>
              <div>
                <span class="bs-sheet-sign-line"></span>
                <p>Date</p>
              </div>
              <button type="button" class="bs-sheet-role" data-go-hash="role-${esc(file.ownerRole)}">Open the ${esc(file.owner)} seat</button>
            </footer>
          </article>
        </div>
      </div>`;
  }

  function renderFiles() {
    const files = window.PK_FILES || [];
    const folders = files.map((file, index) => `
      <button type="button" class="bs-folder" data-go-hash="file-${esc(file.id)}">
        <span class="bs-folder-tab"><b>${fileNo(index)}</b> ${esc(file.name)}</span>
        <span class="bs-folder-sheet">
          <span class="bs-folder-stamp">Blank</span>
          <strong>${esc(file.name)}</strong>
          <span class="bs-folder-rule"></span>
          <span class="bs-folder-rule"></span>
          <span class="bs-folder-rule"></span>
          <em>Kept by ${esc(file.owner)}</em>
        </span>
      </button>`).join("");
    return `
      <div class="page bs-files-page">
        ${systemTabs("files")}
        <div class="bs-desk">
          <div class="bs-drawer">
            <p class="bs-drawer-label">Company cabinet</p>
            <h1 class="serif">Files</h1>
            <p class="bs-drawer-note">Five paper files. Open one. A blank line stays blank until the person named on the file writes it.</p>
            <div class="bs-folders">${folders}</div>
          </div>
        </div>
      </div>`;
  }

  function reportNo(index) {
    const n = index + 1;
    return "R-" + (n < 10 ? "0" + n : String(n));
  }

  function renderReportBody(report) {
    const reports = window.PK_REPORTS || [];
    const index = reports.findIndex((item) => item.id === report.id);
    const number = reportNo(index < 0 ? 0 : index);
    const lines = (report.lines || []).map((line, i) => `
      <div class="bs-sheet-field">
        <span class="bs-sheet-num">${i + 1}</span>
        <div>
          <p class="bs-sheet-label">${esc(line.field)}</p>
          <p class="bs-sheet-line is-blank"></p>
          <p class="bs-sheet-hint">${esc(line.hint)}</p>
        </div>
      </div>`).join("");
    return `
      <div class="page bs-files-page">
        ${systemTabs("report-" + report.id)}
        <button type="button" class="rd-back bs-files-back" data-go-hash="reports"><span class="mso">arrow_back</span> Reports</button>
        <div class="bs-desk">
          <article class="bs-sheet">
            <div class="bs-sheet-holes" aria-hidden="true"><i></i><i></i><i></i></div>
            <div class="bs-sheet-fold" aria-hidden="true"></div>
            <header class="bs-sheet-head">
              <div>
                <p class="bs-sheet-brand">Panun Kaergar</p>
                <p class="bs-sheet-kind">Report form</p>
              </div>
              <div class="bs-sheet-meta">
                <p><span>Report</span> ${esc(number)}</p>
                <p><span>Status</span> Blank form</p>
                <p><span>Copy</span> To fill</p>
              </div>
            </header>
            <h1 class="serif">${esc(report.name)}</h1>
            <p class="bs-sheet-lede">${esc(report.rule)}</p>
            <dl class="bs-sheet-who">
              <div><dt>Written by</dt><dd>${esc(report.writer)}</dd></div>
              <div><dt>When</dt><dd>${esc(report.when)}</dd></div>
              <div><dt>Given to</dt><dd>${esc(report.to)}</dd></div>
            </dl>
            <section>
              <h2>What to write on each line</h2>
              <p class="bs-sheet-intro">The line is blank. The sentence under it says what to write and which record to copy. If you cannot point at that record, leave the line blank and send the gap back the same day. Do not type a remembered figure.</p>
              ${lines}
            </section>
            <p class="bs-sheet-stop"><b>If a line is blank.</b> Send it back the same day and name the record that is missing. Do not invent the number so the report looks finished.</p>
            <footer class="bs-sheet-sign">
              <div>
                <span class="bs-sheet-sign-line"></span>
                <p>${esc(report.writer)}</p>
              </div>
              <div>
                <span class="bs-sheet-sign-line"></span>
                <p>Date</p>
              </div>
              <button type="button" class="bs-sheet-role" data-go-hash="role-${esc(report.writerRole)}">Open the ${esc(report.writer)} seat</button>
            </footer>
          </article>
        </div>
      </div>`;
  }

  function renderReports() {
    const reports = window.PK_REPORTS || [];
    const groups = [];
    reports.forEach((report) => {
      if (!groups.length || groups[groups.length - 1].name !== report.group) {
        groups.push({ name: report.group, items: [] });
      }
      groups[groups.length - 1].items.push(report);
    });
    const drawers = groups.map((group) => {
      const folders = group.items.map((report) => {
        const index = reports.indexOf(report);
        return `
          <button type="button" class="bs-folder" data-go-hash="report-${esc(report.id)}">
            <span class="bs-folder-tab"><b>${reportNo(index)}</b> ${esc(report.name)}</span>
            <span class="bs-folder-sheet">
              <span class="bs-folder-stamp">Form</span>
              <strong>${esc(report.name)}</strong>
              <span class="bs-folder-rule"></span>
              <span class="bs-folder-rule"></span>
              <span class="bs-folder-rule"></span>
              <em>${esc(report.writer)}</em>
            </span>
          </button>`;
      }).join("");
      return `
        <div class="bs-drawer">
          <p class="bs-drawer-label">${esc(group.name)}</p>
          <div class="bs-folders">${folders}</div>
        </div>`;
    }).join("");
    return `
      <div class="page bs-files-page">
        ${systemTabs("reports")}
        <div class="bs-desk">
          <div class="bs-drawer">
            <p class="bs-drawer-label">Report cabinet</p>
            <h1 class="serif">Reports</h1>
            <p class="bs-drawer-note">Open a form. Each blank line has a sentence under it that says what to write, and which record to copy it from. If that record does not exist, leave the line blank.</p>
          </div>
          ${drawers}
        </div>
      </div>`;
  }

  function renderWorkflows(tabId) {
    const tabs = G.workflowTabs || [];
    const active = (G.workflows && G.workflows[tabId]) ? tabId : "growth";
    const wf = G.workflows && G.workflows[active];
    if (!wf) {
      return `<div class="page rd-page"><div class="rd-top"><button type="button" class="rd-back" data-go-home><span class="mso">arrow_back</span> Organisation</button></div><p class="rd-result">Growth workflows are not loaded.</p></div>`;
    }
    const tabHtml = tabs.map((tab) => {
      if (tab.soon) {
        return `<button type="button" class="wf-tab is-soon" disabled>${esc(tab.name)}<em>Next</em></button>`;
      }
      return `<button type="button" class="wf-tab${tab.id === active ? " is-on" : ""}" data-go-hash="workflow-${tab.id}">${esc(tab.name)}</button>`;
    }).join("");
    const heads = Object.assign({
      rules: { file: "sec-rules.png", kicker: "Do not mix", title: "Hard rules", lede: "Growth makes the enquiry. Customer Experience books the job and finishes it. Growth does not book. If Growth also books, nobody owns the result." },
      calendar: { file: "sec-when.png", kicker: "Rhythm", title: "When this runs", lede: "Daily handoff, weekly review, monthly signed plan. Same time so the system does not depend on mood." },
      boundaries: { file: "sec-owns.png", kicker: "Boundaries", title: "Who owns which result", lede: "Each function has one result. Do not steal the next box." },
      paths: { file: "sec-how.png", kicker: "The paths", title: "The three Growth paths", lede: "A new service, a new town, or the weekly engine for what we already sell. Each path has a start, a done, and a stop." },
      seats: { file: "sec-do.png", kicker: "The seats", title: "What each Growth person does", lede: "One result each. Click through for the full playbook: reports, data, and who to pass it to." }
    }, wf.sectionHeads || {});
    const rules = listHtml(wf.rules, "rd-list is-rule");
    const paths = (wf.paths || []).map((path, index) => `
      <article class="wf-path" id="wf-${esc(path.id)}">
        <div class="wf-path-art"><img src="${art(path.art)}" alt=""></div>
        <p class="rd-kicker">Path ${index + 1}</p>
        <h3 class="serif">${esc(path.title)}</h3>
        <p class="wf-why">${esc(path.why)}</p>
        ${path.input ? `<p class="wf-why"><b>Starts with.</b> ${esc(path.input)}</p>` : ""}
        ${path.output ? `<p class="wf-why"><b>Done when.</b> ${esc(path.output)}</p>` : ""}
        ${path.fail ? `<p class="wf-why"><b>Stop if.</b> ${esc(path.fail)}</p>` : ""}
        <ol class="wf-steps">${(path.steps || []).map((step, i) => `
          <li>
            <i>${i + 1}</i>
            <div>
              <b>${esc(step.who)}</b>
              <span>${esc(step.does)}</span>
            </div>
          </li>
        `).join("")}</ol>
      </article>
    `).join("");
    const calendar = (wf.calendar || []).map((item) => `
      <article>
        <h3>${esc(item.when)}</h3>
        <p>${esc(item.what)}</p>
      </article>
    `).join("");
    const boundaries = (wf.boundaries || []).map((item) => `
      <article class="rd-duty">
        <h3>${esc(item.who)}</h3>
        <p>${esc(item.does)}</p>
      </article>
    `).join("");
    const who = (G.roleIds || []).filter((id) => {
      const role = G.roles[id];
      if (!role) return false;
      return (role.dept || "growth") === active;
    }).map((id) => {
      const role = G.roles[id];
      return `<article class="wf-who">
        <img src="${art(role.hero)}" alt="">
        <h3>${esc(role.name)}</h3>
        <p>${esc(role.result)}</p>
        <button type="button" data-go-hash="role-${id}">View role</button>
      </article>`;
    }).join("");
    return `
      <div class="page rd-page wf-page">
        <div class="rd-top">
          <div class="rd-top-row">
            <button type="button" class="rd-back" data-go-home><span class="mso">arrow_back</span> Organisation</button>
          </div>
          <div class="wf-tabs" role="tablist">${tabHtml}</div>
        </div>
        <header class="rd-hero">
          <div class="rd-hero-art"><img src="${art(wf.hero)}" alt=""></div>
          <div class="rd-copy">
            <p class="rd-kicker">${esc(wf.kicker)}</p>
            <h1 class="serif">${esc(wf.name)}</h1>
            <p class="rd-result">${esc(wf.lede)}</p>
            ${wf.story ? `<p class="rd-story">${esc(wf.story)}</p>` : ""}
          </div>
        </header>
        <section class="rd-section">
          ${secHead(heads.rules.file, heads.rules.kicker, heads.rules.title, heads.rules.lede)}
          ${rules}
        </section>
        ${calendar ? `<section class="rd-section">
          ${secHead(heads.calendar.file, heads.calendar.kicker, heads.calendar.title, heads.calendar.lede)}
          <div class="rd-when">${calendar}</div>
        </section>` : ""}
        ${boundaries ? `<section class="rd-section">
          ${secHead(heads.boundaries.file, heads.boundaries.kicker, heads.boundaries.title, heads.boundaries.lede)}
          <div class="rd-duties">${boundaries}</div>
        </section>` : ""}
        <section class="rd-section">
          ${secHead(heads.paths.file, heads.paths.kicker, heads.paths.title, heads.paths.lede)}
          <div class="wf-paths">${paths}</div>
        </section>
        <section class="rd-section">
          ${secHead(heads.seats.file, heads.seats.kicker, heads.seats.title, heads.seats.lede)}
          <div class="wf-who-grid">${who}</div>
        </section>
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
    const btn = branch.querySelector(":scope > .org-card-wrap > .org-toggle");
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

  function bindHashClicks(scope) {
    scope.querySelectorAll("[data-go-home]").forEach((btn) => {
      btn.addEventListener("click", (event) => {
        event.preventDefault();
        goHome();
      });
    });
    scope.querySelectorAll("[data-go-hash]").forEach((btn) => {
      btn.addEventListener("click", (event) => {
        event.preventDefault();
        goHash(btn.getAttribute("data-go-hash"));
      });
    });
  }

  function bindOverview() {
    bindHashClicks(byId("bs-main"));
    byId("bs-main").querySelectorAll("[data-select]").forEach((card) => {
      card.addEventListener("click", () => selectCard(card.dataset.select));
      card.addEventListener("keydown", (event) => {
        if (event.key === "Enter" || event.key === " ") {
          event.preventDefault();
          if (ROLE_IDS[card.dataset.select]) goHash("role-" + card.dataset.select);
          else selectCard(card.dataset.select);
        }
      });
    });
    byId("bs-main").querySelectorAll("[data-open-role]").forEach((btn) => {
      btn.addEventListener("click", (event) => {
        event.preventDefault();
        event.stopPropagation();
        goHash("role-" + btn.getAttribute("data-open-role"));
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
    const wfBtn = byId("bs-main").querySelector("[data-open-workflows]");
    if (outBtn) outBtn.addEventListener("click", () => zoomTo(zoom / 1.18));
    if (inBtn) inBtn.addEventListener("click", () => zoomTo(zoom * 1.18));
    if (fitBtn) fitBtn.addEventListener("click", () => fitTree());
    if (wfBtn) wfBtn.addEventListener("click", () => goHash("workflow-growth"));
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

  let stopRoleSpy = () => {};

  function bindRole() {
    const main = byId("bs-main");
    const page = main && main.querySelector(".rd-page");
    stopRoleSpy();
    if (!page) return;
    bindHashClicks(page);
    const jumps = [...page.querySelectorAll(".rd-jump")];
    const sections = jumps.map((btn) => page.querySelector("#" + btn.getAttribute("data-jump"))).filter(Boolean);
    const stickyOffset = () => {
      const sticky = page.querySelector(".rd-top");
      return (sticky ? sticky.getBoundingClientRect().height : 12) + 10;
    };
    const setOn = (id) => {
      jumps.forEach((btn) => btn.classList.toggle("is-on", btn.getAttribute("data-jump") === id));
    };
    const syncJump = () => {
      if (!sections.length) return;
      const marker = main.getBoundingClientRect().top + stickyOffset();
      let current = sections[0];
      for (const section of sections) {
        if (section.getBoundingClientRect().top <= marker + 1) current = section;
      }
      setOn(current.id);
    };
    jumps.forEach((btn) => {
      btn.addEventListener("click", () => {
        const el = page.querySelector("#" + btn.getAttribute("data-jump"));
        if (!el) return;
        const top = el.getBoundingClientRect().top - main.getBoundingClientRect().top + main.scrollTop - stickyOffset();
        main.scrollTop = Math.max(0, top);
        setOn(btn.getAttribute("data-jump"));
      });
    });
    bindStdModal(page);
    bindAccordions(page);
    bindGlossary(page, stickyOffset, setOn);
    if (!sections.length) return;
    let ticking = false;
    const onScroll = () => {
      if (ticking) return;
      ticking = true;
      requestAnimationFrame(() => {
        ticking = false;
        syncJump();
      });
    };
    main.addEventListener("scroll", onScroll, { passive: true });
    stopRoleSpy = () => {
      main.removeEventListener("scroll", onScroll);
      stopRoleSpy = () => {};
    };
    syncJump();
  }

  function bindGlossary(page, stickyOffset, setOn) {
    const goTerm = (id) => {
      const el = page.querySelector("#gloss-" + id);
      if (!el) return;
      const modal = page.querySelector(".rd-modal");
      if (modal && !modal.hidden) {
        modal.hidden = true;
        const body = modal.querySelector(".rd-modal-body");
        if (body) body.innerHTML = "";
      }
      page.querySelectorAll(".rd-gloss-item.is-target").forEach((node) => node.classList.remove("is-target"));
      const main = byId("bs-main");
      const top = el.getBoundingClientRect().top - main.getBoundingClientRect().top + main.scrollTop - stickyOffset();
      main.scrollTop = Math.max(0, top);
      if (setOn) setOn("rd-gloss");
      el.classList.add("is-target");
      window.setTimeout(() => el.classList.remove("is-target"), 2400);
    };
    const onTerm = (event) => {
      const term = event.target.closest("[data-gloss]");
      if (!term || !page.contains(term)) return;
      if (event.type === "keydown" && event.key !== "Enter" && event.key !== " ") return;
      event.preventDefault();
      event.stopPropagation();
      goTerm(term.getAttribute("data-gloss"));
    };
    page.addEventListener("click", onTerm, true);
    page.addEventListener("keydown", onTerm, true);
  }

  function bindStdModal(page) {
    const modal = page.querySelector(".rd-modal");
    if (!modal) return;
    const body = modal.querySelector(".rd-modal-body");
    const onKey = (event) => {
      if (event.key === "Escape") close();
    };
    const open = (index) => {
      const src = page.querySelector("#rd-std-full-" + index);
      if (!src || !body) return;
      body.innerHTML = src.innerHTML;
      modal.hidden = false;
      document.addEventListener("keydown", onKey);
    };
    const close = () => {
      modal.hidden = true;
      body.innerHTML = "";
      document.removeEventListener("keydown", onKey);
    };
    page.querySelectorAll("[data-std]").forEach((btn) => {
      btn.addEventListener("click", () => open(btn.getAttribute("data-std")));
    });
    modal.querySelectorAll("[data-std-close]").forEach((btn) => {
      btn.addEventListener("click", close);
    });
  }

  function bindAccordions(page) {
    page.querySelectorAll(".rd-acc-item").forEach((item) => {
      const head = item.querySelector(".rd-acc-head");
      const clip = item.querySelector(".rd-acc-head-clip");
      const body = item.querySelector(".rd-acc-body");
      const inner = item.querySelector(".rd-acc-body-inner");
      if (!clip || !body || !inner) return;
      const compact = Math.max(clip.scrollHeight, 72);
      const setBodyH = (px, instant) => {
        if (instant) body.style.transition = "none";
        body.style.height = typeof px === "number" ? px + "px" : px;
        if (instant) {
          item.offsetHeight;
          body.style.transition = "";
        }
      };
      const measureOpen = () => {
        body.style.transition = "none";
        body.style.height = "auto";
        const h = inner.scrollHeight;
        body.style.height = compact + "px";
        item.offsetHeight;
        body.style.transition = "";
        return h;
      };
      setBodyH(compact, true);
      const setOpen = (open) => {
        item.classList.toggle("is-open", open);
        if (head) head.setAttribute("aria-expanded", open ? "true" : "false");
        if (open) setBodyH(measureOpen());
        else setBodyH(compact);
      };
      body.addEventListener("transitionend", (event) => {
        if (event.propertyName !== "height") return;
        if (item.classList.contains("is-open")) body.style.height = "auto";
      });
      const toggle = () => {
        if (item.classList.contains("is-open")) {
          setBodyH(inner.scrollHeight, true);
          setOpen(false);
        } else {
          setOpen(true);
        }
      };
      item.querySelectorAll(".rd-acc-head, .rd-acc-close").forEach((btn) => {
        btn.addEventListener("click", toggle);
      });
    });
  }

  function bindWorkflows() {
    const page = byId("bs-main").querySelector(".rd-page");
    if (!page) return;
    bindHashClicks(page);
  }

  function render() {
    setGlossary([]);
    const hash = hashOf();
    stopRoleSpy();
    window.removeEventListener("resize", onViewportChange);
    if (hash.indexOf("role-") === 0) {
      const id = hash.slice(5);
      const role = G.roles && G.roles[id];
      if (role) {
        byId("bs-main").innerHTML = renderRole(role);
        byId("bs-main").scrollTop = 0;
        bindRole();
        return;
      }
    }
    if (hash === "files") {
      byId("bs-main").innerHTML = renderFiles();
      byId("bs-main").scrollTop = 0;
      bindHashClicks(byId("bs-main"));
      return;
    }
    if (hash === "reports") {
      byId("bs-main").innerHTML = renderReports();
      byId("bs-main").scrollTop = 0;
      bindHashClicks(byId("bs-main"));
      return;
    }
    if (hash.indexOf("report-") === 0) {
      const id = hash.slice(7);
      const report = (window.PK_REPORTS || []).filter((item) => item.id === id)[0];
      if (report) {
        byId("bs-main").innerHTML = renderReportBody(report);
        byId("bs-main").scrollTop = 0;
        bindHashClicks(byId("bs-main"));
        return;
      }
    }
    if (hash.indexOf("file-") === 0) {
      const id = hash.slice(5);
      const file = (window.PK_FILES || []).filter((item) => item.id === id)[0];
      if (file) {
        byId("bs-main").innerHTML = renderFileBody(file);
        byId("bs-main").scrollTop = 0;
        bindHashClicks(byId("bs-main"));
        return;
      }
    }
    if (hash === "workflows" || hash.indexOf("workflow-") === 0) {
      const tab = hash === "workflows" ? "growth" : hash.slice("workflow-".length);
      byId("bs-main").innerHTML = renderWorkflows(tab);
      byId("bs-main").scrollTop = 0;
      bindWorkflows();
      return;
    }
    byId("bs-main").innerHTML = renderOverview();
    byId("bs-main").scrollTop = 0;
    bindOverview();
  }

  window.addEventListener("hashchange", render);
  render();
})();
