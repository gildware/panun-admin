(function () {
  "use strict";
  const root = document.getElementById("os-explorer");
  if (!root || !window.PK_SYSTEM || !window.PK_OS) return;
  const byId = (id) => root.querySelector("#" + id);

const DATA = window.PK_SYSTEM;
    const OS = window.PK_OS;
    const STORY = window.PK_STORY || { packs: {}, sections: {}, groups: {}, connections: {}, flowArt: {} };
    const V = root.getAttribute("data-version") || "os30";
    const art = (file) => (root.getAttribute("data-art-base") || "").replace(/\/$/, "") + "/" + file + "?v=" + V;
    const ART = {
      hero: art("pk-hero-pillars.png"),
      cycle: art("pk-cycle-loop.png"),
      control: art("pk-control-span.png"),
      handoff: art("pk-handoff-object.png"),
      group: {
        growth: art("pk-icon-growth.png"),
        operations: art("pk-icon-operations.png"),
        control: art("pk-icon-control.png")
      },
      groupScene: {
        growth: art("pk-scene-growth.png"),
        operations: art("pk-scene-operations.png"),
        control: art("pk-scene-control.png")
      },
      pack: {
        mi: art("pk-icon-mi.png"), mkt: art("pk-icon-mkt.png"), sd: art("pk-icon-sd.png"),
        me: art("pk-icon-me.png"), pc: art("pk-icon-pc.png"), lms: art("pk-icon-lms.png"),
        co: art("pk-icon-co.png"), cx: art("pk-icon-cx.png"), po: art("pk-icon-po.png"),
        rsm: art("pk-icon-rsm.png"), qm: art("pk-icon-qm.png"), fpc: art("pk-icon-fpc.png"),
        hr: art("pk-icon-hr.png"), td: art("pk-icon-td.png")
      },
      scene: {
        mi: art("pk-scene-mi.png"), mkt: art("pk-scene-mkt.png"), sd: art("pk-scene-sd.png"),
        me: art("pk-scene-me.png"), pc: art("pk-scene-pc.png"), lms: art("pk-scene-lms.png"),
        co: art("pk-scene-co.png"), cx: art("pk-scene-cx.png"), po: art("pk-scene-po.png"),
        rsm: art("pk-scene-rsm.png"), qm: art("pk-scene-qm.png"), fpc: art("pk-scene-fpc.png"),
        hr: art("pk-scene-hr.png"), td: art("pk-scene-td.png")
      },
      section: {
        purpose: art("pk-sec-purpose.png"), role: art("pk-sec-role.png"),
        responsibilities: art("pk-sec-responsibilities.png"), processes: art("pk-sec-processes.png"),
        sops: art("pk-sec-sops.png"), daily: art("pk-sec-daily.png"), weekly: art("pk-sec-weekly.png"),
        monthly: art("pk-sec-monthly.png"), kpis: art("pk-sec-kpis.png"), checklists: art("pk-sec-checklists.png"),
        forms: art("pk-sec-forms.png"), reports: art("pk-sec-reports.png"), handoffs: art("pk-sec-handoffs.png"),
        escalation: art("pk-sec-escalation.png"), improvement: art("pk-sec-improvement.png"),
        statuses: art("pk-sec-statuses.png"), lifecycles: art("pk-sec-lifecycles.png"),
        requests: art("pk-sec-requests.png"), commercial: art("pk-sec-commercial.png"),
        boundary: art("pk-sec-boundary.png")
      },
      flow: {
        demand: art("pk-flow-demand.png"), fulfil: art("pk-flow-fulfil.png"),
        money: art("pk-flow-money.png"), learn: art("pk-flow-learn.png"),
        boundary: art("pk-flow-boundary.png"), people: art("pk-flow-people.png"),
        hero: art("pk-hero-pillars.png"), td: art("pk-scene-td.png")
      }
    };
    let renderedKey = "";
    let packSpyAbort = null;
    let packSpyIgnoreUntil = 0;
    const CHECK_KEY = "pk-modules-reviewed";
    const packById = (id) => DATA.packs.find((p) => p.id === id);
    const packIcon = (id) => ART.pack[id] || ART.group[(packById(id) || {}).group] || ART.group.growth;
    const packScene = (id) => ART.scene[id] || ART.groupScene[(packById(id) || {}).group] || ART.hero;
    const packStory = (id) => STORY.packs[id] || {};
    const secMeta = (id) => STORY.sections[id] || { why: "", how: "" };

    function imgScene(src, alt) {
      return `<img class="art-scene" src="${src}" alt="${esc(alt)}">`;
    }

    function pageIntro(src, alt, kicker, title, body) {
      return `<div class="pack-intro">
        <img class="pack-intro-art" src="${src}" alt="${esc(alt)}">
        <div class="pack-intro-copy">
          <div class="kicker">${esc(kicker)}</div>
          <h2 class="serif">${esc(title)}</h2>
          ${body || ""}
        </div>
      </div>`;
    }

    function sectionBanner(secId) {
      const meta = secMeta(secId);
      const src = ART.section[secId] || ART.section.purpose;
      return `<div class="sec-banner">
        <img src="${src}" alt="">
        <div>
          <div class="kicker">${esc(meta.why || "This section")}</div>
          <h3>${esc(meta.why || secId)}</h3>
          <p class="explain" style="margin:6px 0 0">${esc(meta.how || "")}</p>
        </div>
      </div>`;
    }

    function howCards(steps) {
      if (!steps || !steps.length) return "";
      return `<div class="how-grid">${steps.map((s, i) => `
        <div class="card how-card">
          <div class="n">${String(i + 1).padStart(2, "0")}</div>
          <h3>${esc(s.title)}</h3>
          <p>${esc(s.text)}</p>
        </div>
      `).join("")}</div>`;
    }

    function flattenGroupPacks(gid) {
      const out = [];
      packsIn(gid).forEach((p) => {
        out.push(p);
        childrenOf(p.id).forEach((c) => out.push(c));
      });
      return out;
    }

    function opsModCard(p) {
      const items = (osMod(p.id).processes || []).map((pr) => pr.name);
      const bullets = items.length
        ? items
        : ((packStory(p.id).how || []).map((s) => s.title));
      return `
        <article class="card click ops-mod-card" data-go="#pack-${p.id}/purpose" role="link">
          <div class="ops-mod-head">
            <img class="mod-ico" src="${packIcon(p.id)}" alt="">
            <div>
              <h3>${esc(p.name)}</h3>
              <p>${esc(p.short || p.question)}</p>
            </div>
          </div>
          ${bullets.length ? `<ul class="ops-bullets">${bullets.map((t) => `<li>${esc(t)}</li>`).join("")}</ul>` : ""}
        </article>
      `;
    }

    function doesGrid(story) {
      if (!story.does) return "";
      return `<div class="does-grid">
        <div class="card"><h3>This seat does</h3>${list(story.does)}</div>
        <div class="card"><h3>This seat does not</h3>${list(story.doesNot || [])}</div>
      </div>`;
    }

    function artSrc(path) {
      if (!path) return "";
      return path.indexOf("?") >= 0 ? path : path + "?v=" + V;
    }

    function pills(items) {
      return `<div class="work-meta">${(items || []).map((x) => `<span class="pill">${esc(x)}</span>`).join("")}</div>`;
    }

    function fieldTable(fields) {
      if (!fields || !fields.length) return "";
      return `<div class="card" style="padding:0;overflow:auto;margin-top:10px"><table>
        <thead><tr><th>Field</th><th>Example</th><th>What good looks like</th></tr></thead>
        <tbody>${fields.map((f) => `<tr><td><b>${esc(f.name)}</b></td><td>${esc(f.example || "")}</td><td>${esc(f.note || "")}</td></tr>`).join("")}</tbody>
      </table></div>`;
    }

    function sampleTable(rows) {
      if (!rows || !rows.length) return "";
      const cols = Object.keys(rows[0]);
      return `<div class="card" style="padding:0;overflow:auto;margin-top:10px"><table>
        <thead><tr>${cols.map((c) => `<th>${esc(c)}</th>`).join("")}</tr></thead>
        <tbody>${rows.map((r) => `<tr>${cols.map((c) => `<td>${esc(r[c] ?? "")}</td>`).join("")}</tr>`).join("")}</tbody>
      </table></div>`;
    }

    function checkList(items) {
      return (items || []).map((it) => `<div class="check-item"><i></i><span>${esc(it)}</span></div>`).join("");
    }

    function handoffNote(h, p) {
      const global = ((STORY.handoffsPage || {}).objects || {})[h.id] || {};
      const local = (p && p.handoffExplain && p.handoffExplain[h.object]) || {};
      return {
        meaning: h.plain || local.meaning || global.meaning || "",
        why: local.why || global.why || "",
        completeWhen: local.completeWhen || global.completeWhen || "",
        returnIf: h.sendBack || local.returnIf || global.returnIf || "",
        receiverDoes: h.nextDoes || local.receiverDoes || global.receiverDoes || "",
        example: local.example || global.example || "",
        packet: h.packet || local.packet || global.packet || []
      };
    }

    function handoffLaneOf(id) {
      return ((STORY.handoffsPage || {}).lanes || []).find((l) => (l.ids || []).includes(id)) || null;
    }

    function deskHref(id) {
      return id === "all" ? "#control-span" : "#pack-" + id + "/handoffs";
    }

    function deskArt(id) {
      return id === "all" ? ART.group.control : packIcon(id);
    }

    function renderDesk(id, role) {
      return `<button class="hf-desk" data-go="${deskHref(id)}">
        <img src="${deskArt(id)}" alt="">
        <div><b>${esc(packName(id))}</b><span>${esc(role)}</span></div>
      </button>`;
    }

    function renderHandoffCard(h, p, tint) {
      const n = handoffNote(h, p);
      const laneTint = tint || (handoffLaneOf(h.id) || {}).tint || "growth";
      const packet = n.packet || [];
      return `<article class="hf-card tint-${laneTint}" id="hf-${h.id}">
        <div class="hf-route">
          ${renderDesk(h.from, "sends")}
          <div class="hf-arrow" aria-hidden="true">→</div>
          <div class="hf-obj">
            <b>${esc(h.object)}</b>
            <em>${esc(h.sop || "")}</em>
          </div>
          <div class="hf-arrow" aria-hidden="true">→</div>
          ${renderDesk(h.to, "receives")}
        </div>
        ${n.meaning ? `<p class="hf-meaning">${esc(n.meaning)}</p>` : ""}
        ${h.trigger ? `<p class="hf-when"><b>Send it when.</b> ${esc(h.trigger)}</p>` : ""}
        ${packet.length ? `<div class="hf-packet"><table>
          <thead><tr><th>Field on the form</th><th>Filled example</th></tr></thead>
          <tbody>${packet.map((row) => `<tr><td>${esc(row.field)}</td><td>${esc(row.value)}</td></tr>`).join("")}</tbody>
        </table></div>` : `<div class="hf-fields">${(h.mustInclude || []).map((f) => `<i>${esc(f)}</i>`).join("")}</div>`}
        ${n.returnIf ? `<div class="hf-back"><b>Send it back if</b> ${esc(n.returnIf)}</div>` : ""}
        ${n.receiverDoes ? `<p class="hf-next"><b>Then ${esc(packName(h.to))}.</b> ${esc(n.receiverDoes)}</p>` : ""}
      </article>`;
    }

    function renderExplainedHandoffs(rows, p, tint) {
      if (!rows.length) return `<p class="lede">None recorded.</p>`;
      return `<div class="hf-list">${rows.map((h) => renderHandoffCard(h, p, tint)).join("")}</div>`;
    }

    function renderPipe(items) {
      return `<div class="pipe" role="list">${items.map((s, n) => {
        const arrow = n ? `<div class="pipe-arrow" aria-hidden="true">→</div>` : "";
        if (s.artifact) return `${arrow}<div class="pipe-obj">${esc(s.artifact)}</div>`;
        if (s.action && !s.pack) return `${arrow}<div class="pipe-act">${esc(s.action)}</div>`;
        const p = packById(s.pack) || { name: s.pack, short: "" };
        return `${arrow}<button class="pipe-node" data-go="#pack-${s.pack}/purpose">
          <img src="${packIcon(s.pack)}" alt="">
          <b>${esc(p.name)}</b>
          <span>${esc(s.action || p.short || "")}</span>
        </button>`;
      }).join("")}</div>`;
    }

    function renderHops(rows) {
      if (!rows.length) return `<p class="lede">None recorded.</p>`;
      return `<div class="hops">${rows.map((h) => `
        <div class="hop">
          <button data-go="${h.from === "all" ? "#control-span" : "#pack-" + h.from + "/handoffs"}">
            <div class="who">
              <img src="${h.from === "all" ? ART.group.control : packIcon(h.from)}" alt="">
              <div>${esc(packName(h.from))}<span>sends</span></div>
            </div>
          </button>
          <div class="hop-mid">
            <b>${esc(h.object)}</b>
            <em>${esc(h.trigger)}${h.sop ? " · " + h.sop : ""}</em>
            <div class="hop-fields">${(h.mustInclude || []).map((f) => `<i>${esc(f)}</i>`).join("")}</div>
          </div>
          <button data-go="${h.to === "all" ? "#control-span" : "#pack-" + h.to + "/handoffs"}">
            <div class="who">
              <img src="${h.to === "all" ? ART.group.control : packIcon(h.to)}" alt="">
              <div>${esc(packName(h.to))}<span>receives</span></div>
            </div>
          </button>
        </div>
      `).join("")}</div>`;
    }
    const groupIcon = (gid) => ART.group[gid] || ART.group.growth;
    const packsIn = (group) => DATA.packs.filter((p) => p.group === group && !p.parent);
    const childrenOf = (id) => DATA.packs.filter((p) => p.parent === id);
    const doneCount = () => DATA.packs.filter((p) => p.status === "complete").length;
    const osMod = (id) => (OS && OS.modules && OS.modules[id]) || { processes: [], checklists: [], forms: [], reports: [], escalation: [], improvement: null };
    const packName = (id) => id === "all" ? "All functions" : ((packById(id) || {}).name || id);
    const handoffsFor = (id) => (OS.handoffs || []).filter((h) => h.from === id || h.to === id || h.from === "all" || h.to === "all");
    const esc = (s) => String(s).replace(/[&<>"']/g, (c) => ({ "&": "&amp;", "<": "&lt;", ">": "&gt;", '"': "&quot;", "'": "&#39;" }[c]));

    function getChecks() {
      try { return JSON.parse(localStorage.getItem(CHECK_KEY) || "{}"); } catch { return {}; }
    }
    function setCheck(id, on) {
      const all = getChecks();
      if (on) all[id] = true; else delete all[id];
      localStorage.setItem(CHECK_KEY, JSON.stringify(all));
    }

    function groupTint(id) {
      return id === "growth" ? "g-growth" : id === "operations" ? "g-operations" : "g-control";
    }

    function renderSidebar() {
      const hash = location.hash.replace("#", "") || "overview";
      const view = hash.split("/")[0];
      const groups = DATA.groups.map((g) => {
        const kids = packsIn(g.id);
        return `
          <button class="nav-label ${view === "group-" + g.id ? "active" : ""}" data-go="#group-${g.id}"><img class="grp-ico" src="${groupIcon(g.id)}" alt=""> ${esc(g.name)} <span class="count">${DATA.packs.filter((p) => p.group === g.id).length}</span></button>
          <div class="nav-kids">
            ${g.id === "operations" ? `<button class="nav-item ${view === "ops-head" ? "active" : ""}" data-go="#ops-head"><span class="mso">badge</span> Head of Operations</button>` : ""}
            ${kids.map((p) => `
              <button class="nav-item ${view === "pack-" + p.id ? "active" : ""}" data-go="#pack-${p.id}">
                <img class="nav-ico" src="${packIcon(p.id)}" alt="">
                ${esc(p.name)}
              </button>
              ${childrenOf(p.id).map((c) => `
                <button class="nav-item child ${view === "pack-" + c.id ? "active" : ""}" data-go="#pack-${c.id}">
                  <img class="nav-ico" src="${packIcon(c.id)}" alt="">
                  ${esc(c.name)}
                </button>
              `).join("")}
            `).join("")}
          </div>
        `;
      }).join("");
      byId("os-sidebar").innerHTML = `
        <button class="nav-item ${view === "overview" ? "active" : ""}" data-go="#overview"><span class="mso">dashboard</span> Master tree</button>
        <button class="nav-item ${view === "cycle" ? "active" : ""}" data-go="#cycle"><span class="mso">sync</span> Operating cycle</button>
        <button class="nav-item ${view === "handoffs" ? "active" : ""}" data-go="#handoffs"><span class="mso">swap_horiz</span> Handoffs</button>
        <button class="nav-item ${view === "control-span" ? "active" : ""}" data-go="#control-span"><span class="mso">hub</span> Control span</button>
        <button class="nav-item ${view === "connections" ? "active" : ""}" data-go="#connections"><span class="mso">account_tree</span> Boundaries</button>
        ${groups}
      `;
      byId("os-sidebar").querySelectorAll("[data-go]").forEach((btn) => {
        btn.addEventListener("click", () => go(btn.dataset.go));
      });
    }

    function closeNav() {
      byId("os-sidebar").classList.remove("open");
      byId("os-scrim").classList.remove("on");
      byId("os-results").classList.remove("open");
    }
    function go(hash) {
      location.hash = hash;
      closeNav();
      byId("os-q").value = "";
    }

    function processChips(id) {
      const items = osMod(id).processes || [];
      if (!items.length) return "";
      return `<div class="proc-row">${items.map((pr) =>
        `<button class="proc-chip" data-go="#pack-${id}/processes">${esc(pr.name)}</button>`
      ).join("")}</div>`;
    }

    function modBox(p) {
      return `<div class="arch-mod">
        <button class="arch-mod-main" data-go="#pack-${p.id}/purpose">
          <img class="mod-ico" src="${packIcon(p.id)}" alt="">
          <div>
            <b>${esc(p.name)}</b>
            <em>${esc(p.question || p.short)}</em>
          </div>
        </button>
        ${processChips(p.id)}
      </div>`;
    }

    function renderGroupMod(p, nested) {
      const st = packStory(p.id);
      const kids = childrenOf(p.id);
      return `
        <article class="card group-mod ${groupTint(p.group)} ${nested ? "nested" : ""}">
          <div class="group-mod-head">
            <img class="mod-ico" src="${packIcon(p.id)}" alt="">
            <div>
              <div class="kicker">${esc(p.short)}</div>
              <h3>${esc(p.name)}</h3>
              <p class="lede" style="margin:0">${esc(p.question)}</p>
            </div>
          </div>
          <p class="explain">${esc(st.inPlain || p.purpose)}</p>
          ${howCards(st.how)}
          ${doesGrid(st)}
          ${st.ifBroken ? `<div class="fail-note"><b>If this seat fails:</b> ${esc(st.ifBroken)}</div>` : ""}
          ${processChips(p.id)}
          <button class="ghost open-pack" data-go="#pack-${p.id}/purpose">Open ${esc(p.name)} pack</button>
          ${kids.map((c) => renderGroupMod(c, true)).join("")}
        </article>
      `;
    }

    function groupRelated(gid) {
      if (gid === "growth") return [
        { go: "#cycle", title: "Operating cycle", text: "How a Growth recommendation becomes demand, a booking, and learning back to Intelligence.", img: ART.cycle },
        { go: "#connections", title: "Boundaries", text: "Marketing creates demand. Sales converts. Operations fulfils. Those three seats do not mix.", img: ART.flow.boundary }
      ];
      if (gid === "operations") return [
        { go: "#cycle", title: "Operating cycle", text: "The customer-facing line from enquiry to settlement and quality.", img: ART.cycle },
        { go: "#handoffs", title: "Handoffs", text: "The named objects that move between these desks.", img: ART.handoff }
      ];
      return [
        { go: "#control-span", title: "Control span", text: "Finance, HR and Technology sit across Growth and Operations — they are not a step in the booking.", img: ART.control },
        { go: "#handoffs", title: "Handoffs", text: "What Control receives from the line, and what it sends back.", img: ART.handoff }
      ];
    }

    function renderOpsHead() {
      const h = (STORY.heads && STORY.heads.operations) || {};
      const desks = flattenGroupPacks("operations");
      const span = desks.map((p) => `
        <button class="card click" type="button" data-go="#pack-${p.id}/purpose">
          <img class="mod-ico" src="${packIcon(p.id)}" alt="">
          <b>${esc(p.name)}</b>
          <span>${esc(p.role)} · reports to ${esc(p.reportsTo)}</span>
          <em>${esc(p.short)}</em>
        </button>`).join("");
      const monitor = h.monitor || {};
      const askShare = (rows, label) => `
        <div class="card">
          <h3>${esc(label)}</h3>
          ${(rows || []).map((r) => `
            <div class="jd-team">
              <button class="ghost" data-go="#pack-${r.pack}/purpose">${esc(r.team)}</button>
              <p>${esc(r.ask || r.share)}</p>
            </div>`).join("")}
        </div>`;
      return `
        <div class="page">
          ${pageIntro(ART.groupScene.operations, "Head of Operations", "Operations Function", h.title || "Head of Operations", `
            <p class="lede">Reports to ${esc(h.reportsTo || "Founder / Management")}</p>
            <p class="explain">${esc(h.purpose || "")}</p>
            <div class="meta-row">
              <span class="pill">Seat: Head of Operations</span>
              <span class="pill">Owns: Operations Function</span>
              <span class="pill">Does not sit in a desk pack</span>
            </div>`)}
          <p class="explain">${esc(h.inPlain || "")}</p>
          ${doesGrid(h)}

          <div class="jd-sec">
            <div class="kicker">Span of control</div>
            <h3 class="serif" style="font-size:24px">Six desks report through this function</h3>
            <p class="explain">Customer Experience is nested under Customer Operations. The Head still reads its board. Quality stays independent enough that a kind apology is not a pass. Open a pack for that desk's SOPs and KPIs — they are not copied here.</p>
            <div class="jd-span">${span}</div>
          </div>

          <div class="jd-sec">
            <div class="kicker">Responsibilities</div>
            <h3 class="serif" style="font-size:24px">Work only this seat can do</h3>
            <p class="explain">If a letter could be done by Sales, CO, CX, PO, RSM or Quality alone, it does not belong here.</p>
            <div class="jd-resp">${(h.responsibilities || []).map((r) => `
              <article class="card">
                <h3>${esc(r.title)}</h3>
                <p class="explain" style="margin:0">${esc(r.text)}</p>
              </article>`).join("")}</div>
          </div>

          <div class="jd-sec">
            <div class="kicker">What to monitor</div>
            <h3 class="serif" style="font-size:24px">Read the line, not every field</h3>
            <p class="explain">Desks keep their registers. The Head watches exceptions and patterns. If the daily board is fiction, the weekly report is a novel.</p>
            <div class="cadence" style="margin-top:12px">
              <div class="card"><h3>Daily</h3>${list(monitor.daily || [])}</div>
              <div class="card"><h3>Weekly</h3>${list(monitor.weekly || [])}</div>
              <div class="card"><h3>Monthly</h3>${list(monitor.monthly || [])}</div>
            </div>
          </div>

          <div class="jd-sec">
            <div class="kicker">Head-level KPIs</div>
            <h3 class="serif" style="font-size:24px">Eight measures this seat is judged on</h3>
            <p class="explain">Desk KPI catalogues stay in the packs. These eight tell whether the Operations Function is working as one line.</p>
            <div class="card" style="padding:0;overflow:auto;margin-top:12px"><table>
              <thead><tr><th>KPI</th><th>Target</th><th>Read from</th></tr></thead>
              <tbody>${(h.kpis || []).map((k) => `<tr>
                <td><b>${esc(k.name)}</b></td>
                <td><span class="pill">${esc(k.target)}</span></td>
                <td>${esc(k.from)}</td>
              </tr>`).join("")}</tbody>
            </table></div>
          </div>

          <div class="jd-sec">
            <div class="kicker">Documents this seat keeps</div>
            <h3 class="serif" style="font-size:24px">Six artefacts — not the CRM</h3>
            <p class="explain">Lead Register, Booking Register, Complaint Register and the rest stay on the desks. If the Head starts a second copy, the system has two truths.</p>
            <div class="card" style="padding:0;overflow:auto;margin-top:12px"><table>
              <thead><tr><th>Document</th><th>Kind</th><th>One row is</th><th>Used by</th></tr></thead>
              <tbody>${(h.documents || []).map((d) => `<tr>
                <td><b>${esc(d.name)}</b></td>
                <td>${esc(d.kind)}</td>
                <td>${esc(d.oneRow)}</td>
                <td>${esc(d.usedBy)}</td>
              </tr>`).join("")}</tbody>
            </table></div>
          </div>

          <div class="jd-sec">
            <div class="kicker">Reports to prepare</div>
            <h3 class="serif" style="font-size:24px">Three outputs. Built from desk reports.</h3>
            <div class="cadence" style="margin-top:12px">${(h.reports || []).map((r) => `
              <div class="card">
                <div class="kicker">${esc(r.when)}</div>
                <h3>${esc(r.name)}</h3>
                <p class="explain">${esc(r.contains)}</p>
                <div class="note"><b>Audience.</b> ${esc(r.audience)}</div>
              </div>`).join("")}</div>
          </div>

          <div class="jd-sec">
            <div class="kicker">Other teams</div>
            <h3 class="serif" style="font-size:24px">What to ask for, and what to send</h3>
            <p class="explain">Ask for a complete object. Share a complete object. Do not share a mood.</p>
            <div class="jd-pair">
              ${askShare(h.ask, "Ask other teams for")}
              ${askShare(h.share, "Share with other teams")}
            </div>
          </div>

          <div class="jd-sec">
            <div class="kicker">SOPs this seat runs</div>
            <h3 class="serif" style="font-size:24px">Six procedures. Desk SOPs stay in the packs.</h3>
            <p class="explain">LMS-SOP-01 is not the Head's job. These six are. Expand the one you are running.</p>
            <div class="toolbar" style="margin:10px 0"><button class="ghost" data-expand="1">Expand all</button><button class="ghost" data-expand="0">Collapse all</button></div>
            ${(h.sops || []).map((s) => `
              <details class="box" style="margin-bottom:8px">
                <summary><span class="sop-id">${esc(s.id)}</span> ${esc(s.title)}</summary>
                <p class="explain" style="padding:0 14px">When: ${esc(s.when || "")}</p>
                <ol class="plain-list">${(s.steps || []).map((st) => `<li>${esc(st)}</li>`).join("")}</ol>
              </details>`).join("")}
          </div>

          <div class="jd-sec">
            <div class="kicker">Escalations that land here</div>
            <h3 class="serif" style="font-size:24px">When a desk cannot finish</h3>
            <div class="card" style="padding:0;overflow:auto;margin-top:12px"><table>
              <thead><tr><th>Trigger</th><th>From</th><th>SLA</th><th>Record</th></tr></thead>
              <tbody>${(h.escalationsIn || []).map((e) => `<tr>
                <td><b>${esc(e.trigger)}</b></td>
                <td>${esc(e.from)}</td>
                <td>${esc(e.sla)}</td>
                <td>${esc(e.record)}</td>
              </tr>`).join("")}</tbody>
            </table></div>
          </div>

          <div class="jd-sec">
            <div class="kicker">Cadence of this seat</div>
            <div class="cadence" style="margin-top:12px">
              <div class="card"><h3>Daily</h3>${list((h.cadence && h.cadence.daily) || [])}</div>
              <div class="card"><h3>Weekly</h3>${list((h.cadence && h.cadence.weekly) || [])}</div>
              <div class="card"><h3>Monthly</h3>${list((h.cadence && h.cadence.monthly) || [])}</div>
            </div>
          </div>

          <div class="note" style="margin-top:22px"><b>No duplicate packs.</b> Lead intake steps, booking assignment, complaint handling, provider verification, billing rules and quality audits live in the six desk packs. This page is only the Head of Operations job.</div>
        </div>
      `;
    }

    function renderGroup(gid) {
      const g = DATA.groups.find((x) => x.id === gid);
      if (!g) return `<div class="page empty">Unknown branch.</div>`;
      const gs = STORY.groups[gid] || {};
      const kids = packsIn(gid);
      const total = DATA.packs.filter((p) => p.group === gid).length;
      const related = groupRelated(gid).map((r) => `
        <button class="card click" data-go="${r.go}">
          <img class="art-thumb" src="${r.img}" alt="">
          <h3>${esc(r.title)}</h3>
          <p>${esc(r.text)}</p>
        </button>
      `).join("");
      const mods = gid === "operations"
        ? `<div class="ops-row">${flattenGroupPacks(gid).map(opsModCard).join("")}</div>`
        : `<div class="group-mods">${kids.map((p) => renderGroupMod(p, false)).join("")}</div>`;
      return `
        <div class="page">
          ${pageIntro(ART.groupScene[gid], g.name, g.name + " branch", gs.title || g.name, `<p class="explain">${esc(gs.explain || g.purpose)}</p>`)}
          <div class="kicker" style="margin-top:22px">${total} modules</div>
          <h3 class="serif" style="font-size:28px">What each function does</h3>
          <p class="explain">${esc(gs.pageLine || "Each card is a desk in this branch. Open the pack for SOPs, KPIs and handoffs.")}</p>
          ${mods}
          ${gid === "operations" ? `
          <div class="kicker" style="margin-top:28px">This branch's owner</div>
          <button class="card click" data-go="#ops-head">
            <div class="kicker">Job description</div>
            <h3>Head of Operations</h3>
            <p>The seat that owns this line: role, what to monitor, documents, reports to prepare, what to ask other teams and what to share. Desk packs stay on the desks — this page does not copy them.</p>
          </button>` : ""}
          <div class="kicker" style="margin-top:28px">Related</div>
          <div class="story-cards">${related}</div>
        </div>
      `;
    }

    function renderOverview() {
      const ov = STORY.overview || {};
      const branches = DATA.groups.map((g) => {
        const kids = packsIn(g.id);
        const gs = STORY.groups[g.id] || {};
        return `
          <div class="branch">
            <div class="vline"></div>
            <div class="branch-col ${groupTint(g.id)}">
              <button class="node-group" data-go="#group-${g.id}">
                <img class="grp-ico" src="${groupIcon(g.id)}" alt="">
                <div>
                  <b>${esc(g.name)}</b>
                  <span>${esc(gs.line || g.purpose)}</span>
                </div>
              </button>
              <div class="branch-mods">
                ${kids.map((p) => modBox(p) + childrenOf(p.id).map((c) => modBox(c)).join("")).join("")}
              </div>
            </div>
          </div>
        `;
      }).join("");
      const groupStories = DATA.groups.map((g) => {
        const gs = STORY.groups[g.id] || {};
        return `<button class="card click ${groupTint(g.id)}" data-go="#group-${g.id}">
          <img class="art-thumb" src="${ART.groupScene[g.id]}" alt="">
          <h3>${esc(gs.title || g.name)}</h3>
          <p>${esc(gs.explain || g.purpose)}</p>
        </button>`;
      }).join("");
      return `
        <div class="page page-arch">
          ${pageIntro(ART.hero, "Growth, Operations and Control as three pillars", "Master operating system", DATA.title, `<p class="explain">${esc(ov.lede || DATA.purpose)}</p>`)}
          <div class="story-cards" style="margin-top:14px">${groupStories}</div>
          <div class="kicker" style="margin-top:28px">The full tree</div>
          <h3 class="serif" style="font-size:28px">Every function, in its seat</h3>
          <p class="explain">${esc(ov.treeHow || "Each box is one function. Click a name to open that module. The chips inside the box are the processes that desk runs.")}</p>
          <div class="arch-scroll">
          <div class="arch">
            <div class="node node-root">
              <b>${esc(DATA.title)}</b>
              <span>Home services operating system</span>
            </div>
            <div class="vline"></div>
            <div class="node node-hub">
              <b>${esc(DATA.grouping)}</b>
              <span>Growth creates · Operations delivers · Control supports all</span>
            </div>
            <div class="vline"></div>
            <div class="arch-branches">${branches}</div>
          </div>
          </div>
        </div>
      `;
    }

    function renderCycle() {
      const cy = STORY.cycle || {};
      const shortSeat = (id) => ({
        mi: "Intelligence", mkt: "Marketing", lms: "Sales",
        co: "Cust. Ops", po: "Providers", cx: "Experience",
        rsm: "Revenue", qm: "Quality"
      }[id] || (packById(id) || {}).name || id);
      const shortAct = (label) => ({
        "Research and recommend": "Research",
        "Create demand": "Demand",
        "Convert enquiry": "Convert",
        "Coordinate fulfilment": "Coordinate",
        "Deploy provider": "Deploy",
        "Support the customer": "Support",
        "Bill, collect, settle": "Settle",
        "Check and correct": "Check",
        "Feed the next growth cycle": "Next cycle"
      }[label] || label);
      const lineNode = (step, last) => {
        if (step.artifact) {
          const name = step.artifact === "Feedback / corrective action" ? "Feedback" : step.artifact;
          return `<div class="line-obj">${esc(name)}</div>`;
        }
        const p = packById(step.pack) || { name: step.pack };
        return `<button class="line-node${last ? " is-loop" : ""}" type="button" data-go="#pack-${step.pack}/purpose" title="${esc(p.name)} — ${esc(step.label)}">
          <img src="${packIcon(step.pack)}" alt="">
          <b>${esc(shortSeat(step.pack))}</b>
          <span>${esc(shortAct(step.label))}</span>
        </button>`;
      };
      const lanes = [
        { label: "Demand", items: [] },
        { label: "Fulfilment", items: [] },
        { label: "Learning", items: [] }
      ];
      let lane = 0;
      const steps = OS.cycle || [];
      steps.forEach((step, i) => {
        lanes[lane].items.push({ step, last: i === steps.length - 1 });
        if (lane === 0 && step.artifact === "Confirmed booking") lane = 1;
        else if (lane === 1 && step.pack === "cx") lane = 2;
      });
      const chart = lanes.map((ln) => {
        const flow = ln.items.map((item, i) =>
          `${i ? '<span class="line-join" aria-hidden="true">→</span>' : ""}${lineNode(item.step, item.last)}`
        ).join("");
        return `<div class="line-lane">
          <div class="line-lane-label">${esc(ln.label)}</div>
          <div class="line-lane-flow">${flow}</div>
        </div>`;
      }).join("");
      return `
        <div class="page">
          ${pageIntro(ART.cycle, "Operating cycle from research through settlement and quality", "The complete Panun Kaergar workflow", "Operating cycle", `<p class="explain">${esc(cy.explain || "")}</p><p class="explain">${esc(cy.objects || "")}</p>`)}
          <div class="flow-trio">
            <img class="flow-trio-art" src="${ART.flow.demand}" alt="How demand is created">
            <img class="flow-trio-art" src="${ART.flow.fulfil}" alt="How a booking is fulfilled">
            <img class="flow-trio-art" src="${ART.flow.learn}" alt="Learning returns to Growth">
          </div>
          <div class="kicker">The line</div>
          <div class="line-chart">${chart}</div>
          <div class="note">Control is not a station on this line — open Control span to see the bar above it.</div>
        </div>
      `;
    }

    function renderHandoffTable(rows) {
      return `<div class="card" style="padding:0;overflow:auto"><table>
        <thead><tr><th>From</th><th>Form</th><th>To</th><th>Send it when</th><th>SOP</th></tr></thead>
        <tbody>${rows.map((h) => `<tr>
          <td><button class="ghost" data-go="${h.from === "all" ? "#control-span" : "#pack-" + h.from + "/handoffs"}">${esc(packName(h.from))}</button></td>
          <td><b>${esc(h.object)}</b><div class="lede" style="margin:0">${esc(h.plain || (h.mustInclude || []).join(" · "))}</div></td>
          <td><button class="ghost" data-go="${h.to === "all" ? "#control-span" : "#pack-" + h.to + "/handoffs"}">${esc(packName(h.to))}</button></td>
          <td>${esc(h.trigger)}</td>
          <td class="sop-id">${esc(h.sop || "")}</td>
        </tr>`).join("")}</tbody>
      </table></div>`;
    }

    function renderHandoffsPage(laneId) {
      const hx = STORY.handoffsPage || {};
      const all = OS.handoffs || [];
      const byId = Object.fromEntries(all.map((h) => [h.id, h]));
      const lanes = hx.lanes || [];
      const used = new Set(lanes.flatMap((l) => l.ids || []));
      const leftover = all.filter((h) => !used.has(h.id));
      const active = lanes.some((l) => l.id === laneId) ? laneId : "all";
      const chips = [
        { id: "all", name: "All", count: all.length },
        ...lanes.map((l) => ({ id: l.id, name: l.name, count: (l.ids || []).length }))
      ].map((c) =>
        `<button class="hf-chip ${c.id === active ? "on" : ""}" type="button" data-lane="${c.id}">${esc(c.name)}<span class="n">${c.count}</span></button>`
      ).join("");
      const laneBlocks = lanes.map((l) => {
        const rows = (l.ids || []).map((id) => byId[id]).filter(Boolean);
        return `<section class="hf-lane" data-lane="${l.id}" ${active !== "all" && active !== l.id ? "hidden" : ""}>
          <div class="hf-lane-head">
            <h3 class="serif">${esc(l.name)}</h3>
            <span class="n">${rows.length} object${rows.length === 1 ? "" : "s"}</span>
          </div>
          <p class="explain">${esc(l.explain || "")}</p>
          ${renderExplainedHandoffs(rows, null, l.tint)}
        </section>`;
      }).join("");
      const extra = leftover.length ? `<section class="hf-lane" data-lane="other">
        <div class="hf-lane-head"><h3 class="serif">Also recorded</h3><span class="n">${leftover.length}</span></div>
        ${renderExplainedHandoffs(leftover, null)}
      </section>` : "";
      return `
        <div class="page hf-page">
          ${pageIntro(ART.handoff, "A complete work object passing from one desk to another", "What turns departments into a system", "Handoffs", `<p class="explain">${esc(hx.explain || "")}</p>`)}
          <div class="hf-anatomy">${(hx.anatomy || []).map((a) => `
            <div class="card"><h3>${esc(a.title)}</h3><p>${esc(a.text)}</p></div>
          `).join("")}</div>
          <div class="hf-pair">
            <div class="note"><b>Complete.</b> ${esc(hx.good || "")}</div>
            <div class="note"><b>Not a handoff.</b> ${esc(hx.bad || "")}</div>
          </div>
          <div class="hf-filter" role="tablist" aria-label="Handoff lanes">${chips}</div>
          ${laneBlocks}
          ${extra}
          <details class="box" style="margin-top:8px">
            <summary>Full register · ${all.length} objects</summary>
            <div style="padding:0 10px 10px">${renderHandoffTable(all)}</div>
          </details>
        </div>
      `;
    }

    function bindHandoffPage(laneId) {
      const page = byId("os-main").querySelector(".hf-page");
      if (!page) return;
      const lanes = page.querySelectorAll(".hf-lane");
      const chips = page.querySelectorAll(".hf-chip");
      const apply = (id) => {
        const lane = id || "all";
        chips.forEach((c) => c.classList.toggle("on", c.dataset.lane === lane));
        lanes.forEach((s) => {
          s.hidden = lane !== "all" && s.dataset.lane !== lane;
        });
      };
      chips.forEach((c) => {
        c.addEventListener("click", () => {
          const lane = c.dataset.lane;
          apply(lane);
          history.replaceState(null, "", lane === "all" ? "#handoffs" : "#handoffs/" + lane);
        });
      });
      apply(lanes.length && [...lanes].some((s) => s.dataset.lane === laneId) ? laneId : "all");
    }

    function renderControlSpan() {
      const cx = STORY.controlPage || {};
      const arms = (OS.controlSpan.arms || []).map((a) => {
        const p = packById(a.pack);
        const st = packStory(a.pack);
        return `<button class="card click g-control span-arm" data-go="#pack-${a.pack}/purpose">
          <img class="art-thumb" src="${packScene(a.pack)}" alt="">
          <img class="mod-ico" src="${packIcon(a.pack)}" alt="">
          <h3>${esc(p.name)}</h3>
          <p>${esc(st.inPlain || a.supports)}</p>
        </button>`;
      }).join("");
      return `
        <div class="page">
          ${pageIntro(ART.control, "Finance, people and technology spanning the operating line", "Supports all functions", "Control span", `<p class="explain">${esc(cx.explain || OS.controlSpan.note)}</p>`)}
          ${pageIntro(ART.flow.money, "Two money layers: booking settlement versus business finance", "Two money layers", "Booking money vs business money", `<p class="explain">Revenue &amp; Settlement closes one job: invoice, collection, commission, payout. Financial Planning &amp; Control owns the business plan, cash and spend limits.</p>`)}
          ${pageIntro(ART.flow.people, "Employees are not providers", "People vs network", "Employees are not providers", `<p class="explain">People &amp; HR owns employees. Provider Operations owns the operating network. A craftsman with a toolbox is not on HR by default.</p>`)}
          <div class="span-hub">
            <img class="grp-ico" src="${groupIcon("control")}" alt="" style="margin:0 auto 8px;display:block">
            <b>Control</b><span>Finance · People · Technology</span>
          </div>
          <div class="span-grid">${arms}</div>
          <div class="note">People &amp; HR owns employees. Provider Operations owns the operating network. Revenue &amp; Settlement owns booking-level money. Financial Planning &amp; Control owns the business financial plan.</div>
        </div>
      `;
    }

    function renderChain(c) {
      const items = c.chain || c.steps.map((s) => ({ pack: s.pack, action: s.action }));
      return items.map((s, n) => {
        const arrow = n ? '<span class="arrow mso">arrow_forward</span>' : "";
        if (s.artifact) return `${arrow}<div class="flow-artifact">${esc(s.artifact)}</div>`;
        if (s.action && !s.pack) return `${arrow}<div class="flow-action">${esc(s.action)}</div>`;
        if (s.pack && !s.action) {
          const p = packById(s.pack);
          return `${arrow}<button class="flow-step" data-go="#pack-${s.pack}"><b>${esc(p.name)}</b></button>`;
        }
        const p = packById(s.pack);
        return `${arrow}<button class="flow-step" data-go="#pack-${s.pack}">
          <div class="n">${String(n + 1).padStart(2, "0")}</div>
          <b>${esc(p.name)}</b>
          <span>${esc(s.action || "")}</span>
        </button>`;
      }).join("");
    }

    function renderConnections() {
      const blocks = DATA.connections.map((c, i) => {
        const teach = (STORY.connections && STORY.connections[c.id]) || {};
        const flowKey = (STORY.flowArt && STORY.flowArt[c.id]) || "";
        const flowSrc = ART.flow[flowKey];
        const items = c.chain || (c.steps || []).map((s) => ({ pack: s.pack, action: s.action }));
        return `
        <article class="card wrap-intro ${c.kind === "boundary" ? "boundary" : ""}" style="margin-top:18px">
          ${flowSrc ? `<img class="pack-intro-art" src="${flowSrc}" alt="">` : ""}
          <div class="pack-intro-copy">
            <div class="kicker">${c.kind === "boundary" ? "Critical system boundary" : "Connection " + (i + 1)}</div>
            <h3 class="serif" style="font-size:24px">${esc(c.title)}</h3>
            <p class="lede">${esc(c.subtitle)}</p>
            <p class="explain">${esc(teach.teach || c.distinction)}</p>
          </div>
          ${renderPipe(items)}
          ${teach.teach ? `<div class="note">${esc(c.distinction)}</div>` : ""}
        </article>`;
      }).join("");
      return `
        <div class="page">
          ${pageIntro(ART.flow.boundary, "Demand, conversion and fulfilment stay in separate seats", "How the modules connect", "System connections", `<p class="explain">These diagrams are the joints of the operating system. Click any node to open that function. Gold blocks are the objects that must be complete before the next seat may start.</p>`)}
          ${blocks}
        </div>
      `;
    }

    function renderPending(p) {
      return `
        <div class="page">
          ${pageIntro(packScene(p.id), p.name, p.group, p.name, `<p class="lede">${esc(p.question)}</p><div class="meta-row"><span class="badge badge-wait">Role pack pending</span></div>`)}
          <div class="pending-panel">
            <span class="mso" style="font-size:36px;color:var(--gold-2)">hourglass_empty</span>
            <h3 class="serif">Awaiting role pack</h3>
            <p class="lede" style="margin:0 auto">${esc(p.short)}. Guiding question is recorded. Detailed role, SOPs, KPIs and records have not been shared yet.</p>
            ${p.note ? `<div class="note" style="text-align:left;max-width:640px;margin:16px auto 0">${esc(p.note)}</div>` : ""}
          </div>
        </div>
      `;
    }

    function list(items) {
      return `<ul class="plain-list">${items.map((x) => `<li>${esc(x)}</li>`).join("")}</ul>`;
    }

    function packTabs(p) {
      const tabs = (OS.sections || []).map(([id, label]) => [id, label]);
      if (p.statuses) tabs.push(["statuses", p.statusLabel || "Status"]);
      if (p.lifecycles) tabs.push(["lifecycles", "Lifecycles"]);
      if (p.requestTypes) tabs.push(["requests", "Request types"]);
      if (p.doesNotOwn || p.boundaryWith) tabs.push(["boundary", "Boundary"]);
      return tabs;
    }

    function aliasTab(tab) {
      return ({ overview: "purpose", cadence: "daily", records: "forms", output: "handoffs" })[tab] || tab;
    }

    function sectionHtml(p, id) {
      const os = osMod(p.id);
      const st = packStory(p.id);
      const banner = sectionBanner(id);
      if (id === "purpose") {
        return `
          ${banner}
          <div class="card">
            <p class="explain" style="margin:0">${esc(st.inPlain || p.purpose)}</p>
            <p class="lede" style="margin-top:10px">${esc(p.purpose)}</p>
            ${p.alias ? `<p class="lede">${esc(p.alias)}</p>` : ""}
            ${p.distinction ? `<div class="note">${esc(p.distinction)}</div>` : ""}
            ${p.handoff ? `<div class="note">${esc(p.handoff)}</div>` : ""}
          </div>
        `;
      }
      if (id === "role") {
        return `${banner}<div class="card">
          <h3>${esc(p.role)}</h3>
          <p class="lede">Reports to ${esc(p.reportsTo)}</p>
          <p class="explain">${esc(st.inPlain || p.purpose)}</p>
          ${p.parent ? `<div class="note">Nested under ${esc(packName(p.parent))}. This is not a separate main module. It still has its own 15-section pack because the work is different.</div>` : ""}
        </div>`;
      }
      if (id === "responsibilities") {
        const illustrated = (p.responsibilities || []).some((r) => r.art || r.purpose);
        if (illustrated) {
          return `
            ${banner}
            <p class="explain">${esc(p.distinction || "Each letter is a different owned patch. If two letters could do the same work, the split is wrong.")}</p>
            <div class="work-stack">${p.responsibilities.map((r) => `
              <article class="work-card">
                ${r.art ? `<img src="${artSrc(r.art)}" alt="${esc(r.title)}">` : ""}
                <div class="work-body">
                  <h3><span class="letter">${esc(r.letter)}</span> ${esc(r.title)}</h3>
                  <p class="explain">${esc(r.purpose || "")}</p>
                  ${pills([r.owns ? "Owns: " + r.owns : "", r.sop].filter(Boolean))}
                  ${r.not ? `<div class="note">${esc(r.not)}</div>` : ""}
                  ${r.registers ? `<p class="spec-kicker">Live registers</p>${pills(r.registers)}` : ""}
                  ${r.sop ? `<button class="ghost" data-go="#pack-${p.id}/sops">${esc(r.sop)}</button>` : ""}
                  <p class="spec-kicker">This letter actually does</p>
                  ${checkList(r.items)}
                </div>
              </article>
            `).join("")}</div>
          `;
        }
        return `
          ${banner}
          <p class="explain">These letters are the owned patches of ${esc(p.name)}. Expand a letter to see the work. If an item feels like another module, it belongs on the Boundary tab.</p>
          <div class="toolbar"><button class="ghost" data-expand="1">Expand all</button><button class="ghost" data-expand="0">Collapse all</button></div>
          <div class="resp">
            ${p.responsibilities.map((r) => `
              <details class="box">
                <summary><span class="letter">${esc(r.letter)}</span> ${esc(r.title)}</summary>
                ${list(r.items)}
              </details>
            `).join("")}
          </div>
        `;
      }
      if (id === "processes") {
        return `${banner}<p class="explain">Each process is a repeating path. The pills are the steps in order. The SOP link is the written procedure behind the path.</p>
          <div class="cards">${os.processes.map((pr, i) => `
          <div class="card">
            <div class="kicker">Process ${String(i + 1).padStart(2, "0")}</div>
            <h3>${esc(pr.name)}</h3>
            <p class="explain" style="margin-top:6px">Outcome: ${esc(pr.outcome)}</p>
            <div class="proc-flow">${(pr.items || []).map((it, n) => `
              ${n ? `<span class="pipe-arrow">→</span>` : ""}
              <span class="proc-step"><span class="pn">${n + 1}</span>${esc(it)}</span>
            `).join("")}</div>
            ${pr.sop ? `<button class="ghost" style="margin-top:10px" data-go="#pack-${p.id}/sops">${esc(pr.sop)}</button>` : ""}
          </div>
        `).join("")}</div>`;
      }
      if (id === "sops") {
        const illustrated = (p.sops || []).some((s) => s.art || s.purpose);
        if (illustrated) {
          return `
            ${banner}
            <p class="explain">Each SOP is a different procedure. Collection is not analysis. Identification is not validation. Reporting does not do new fieldwork.</p>
            <div class="work-stack">${p.sops.map((s) => `
              <article class="work-card">
                ${s.art ? `<img src="${artSrc(s.art)}" alt="${esc(s.title)}">` : ""}
                <div class="work-body">
                  <div class="kicker">${esc(s.id)}</div>
                  <h3>${esc(s.title)}</h3>
                  <p class="explain">${esc(s.purpose || "")}</p>
                  ${s.when ? `<p class="spec-kicker">When</p><p class="explain" style="margin-top:4px">${esc(s.when)}</p>` : ""}
                  ${s.inputs ? `<p class="spec-kicker">Inputs</p>${pills(s.inputs)}` : ""}
                  ${s.uses ? `<p class="spec-kicker">Writes to</p>${pills(s.uses)}` : ""}
                  <p class="spec-kicker">Steps — do not skip</p>
                  <ol class="plain-list">${(s.steps || []).map((st) => `<li>${esc(st)}</li>`).join("")}</ol>
                  ${s.output ? `<div class="note"><b>Done when.</b> ${esc(s.output)}</div>` : ""}
                </div>
              </article>
            `).join("")}</div>
          `;
        }
        return `
          ${banner}
          <p class="explain">Procedures stay collapsed so you can scan titles. Open the one you are running. Do not skip a step to save time — the next function will inherit the defect.</p>
          <div class="toolbar"><button class="ghost" data-expand="1">Expand all</button><button class="ghost" data-expand="0">Collapse all</button></div>
          ${p.sops.map((s) => `
            <details class="box" style="margin-bottom:8px">
              <summary><span class="sop-id">${esc(s.id)}</span> ${esc(s.title)}</summary>
              <ol class="plain-list">${s.steps.map((st) => `<li>${esc(st)}</li>`).join("")}</ol>
            </details>
          `).join("")}
        `;
      }
      if (id === "daily" || id === "weekly" || id === "monthly") {
        const label = id[0].toUpperCase() + id.slice(1);
        return `${banner}<div class="card"><h3>${label} work for ${esc(p.name)}</h3>
          <p class="explain">${id === "daily" ? "Keep the board true." : id === "weekly" ? "Read patterns, not panic." : "Decide what the system should change."}</p>
          ${list(p.cadence[id] || [])}
        </div>`;
      }
      if (id === "kpis") {
        const grouped = {};
        p.kpis.forEach((k) => {
          const g = k.group || "Measures";
          (grouped[g] = grouped[g] || []).push(k);
        });
        return `${banner}<p class="explain">A target without an owner is decoration. Each measure should be produced by a process or SOP on this pack.</p>
          ${Object.keys(grouped).map((g) => `
            <div class="card" style="margin-top:10px">
              <div class="kicker">${esc(g)}</div>
              ${grouped[g].map((k) => `
                <div class="kpi-row">
                  <div>
                    <b>${esc(k.name)}</b>
                    <div class="kpi-meter"><span></span></div>
                  </div>
                  <span class="pill">${esc(k.target)}</span>
                </div>
              `).join("")}
            </div>
          `).join("")}`;
      }
      if (id === "checklists") {
        return `${banner}<p class="explain">A checklist is a gate. If an item is unchecked, the object is not ready to hand off and the weekly report is not honest.</p>
          <div class="work-stack">${os.checklists.map((c) => `
            <div class="card">
              <div class="kicker">${esc(c.when)}</div>
              <h3>${esc(c.name)}</h3>
              ${c.purpose ? `<p class="explain">${esc(c.purpose)}</p>` : ""}
              ${checkList(c.items)}
            </div>
          `).join("")}</div>`;
      }
      if (id === "forms") {
        if (p.formSpecs && p.formSpecs.length) {
          return `${banner}
            <p class="explain">These are the live registers. Each has a different grain of row. If two registers could hold the same fact, the design is leaking.</p>
            <div class="work-stack">${p.formSpecs.map((f) => `
              <article class="card">
                <div class="kicker">${esc(f.kind || "Register")}</div>
                <h3>${esc(f.name)}</h3>
                <p class="explain"><b>One row is:</b> ${esc(f.oneRow || "")}</p>
                ${f.usedBy ? `<p class="spec-kicker">Filled by ${esc(f.usedBy)}</p>` : ""}
                ${f.notHere ? `<div class="note">${esc(f.notHere)}</div>` : ""}
                <p class="spec-kicker">Fields</p>
                ${fieldTable(f.fields)}
                <p class="spec-kicker">Example rows</p>
                ${sampleTable(f.sample)}
              </article>
            `).join("")}</div>`;
        }
        return `${banner}<p class="explain">These are the places ${esc(p.name)} writes the work down. Memory is not a record.</p>
          <div class="chips">${os.forms.map((r) => `<span class="rec">${esc(r)}</span>`).join("")}</div>`;
      }
      if (id === "reports") {
        if (p.reportSpecs && p.reportSpecs.length) {
          return `${banner}
            <p class="explain">A report is compiled from registers. It is not a place to invent new facts. Each section below is required — if you cannot fill it, the source SOP is unfinished.</p>
            <div class="work-stack">${p.reportSpecs.map((r) => `
              <article class="card">
                <div class="kicker">${esc(r.when)}</div>
                <h3>${esc(r.name)}</h3>
                <p class="explain">${esc(r.purpose || "")}</p>
                ${r.notThis ? `<div class="note">${esc(r.notThis)}</div>` : ""}
                <p class="spec-kicker">Who reads it</p>
                <p class="explain" style="margin-top:4px">${esc(r.reader || "")}</p>
                ${(r.sections || []).map((s) => `
                  <div class="note" style="margin-top:10px"><b>${esc(s.title)}.</b> ${esc(s.must)}</div>
                `).join("")}
                ${r.sampleLine ? `<p class="spec-kicker">Sounds like</p><div class="note">${esc(r.sampleLine)}</div>` : ""}
              </article>
            `).join("")}</div>`;
        }
        return `${banner}<p class="explain">Reports are compressed truth from the same registers, not a separate story.</p>
          <div class="chips">${os.reports.map((r) => `<span class="rec">${esc(r)}</span>`).join("")}</div>
          ${!os.reports.length ? `<p class="lede">No separate report names were listed; management reporting is in cadence and SOPs.</p>` : ""}`;
      }
      if (id === "handoffs") {
        const inbound = (OS.handoffs || []).filter((h) => h.to === p.id);
        const support = (OS.handoffs || []).filter((h) => h.to === "all");
        const outbound = (OS.handoffs || []).filter((h) => h.from === p.id);
        const supportOut = (OS.handoffs || []).filter((h) => h.from === "all");
        return `
          ${banner}
          <p class="explain" style="margin-top:0">Each card is a filled form ${esc(p.name)} sends or receives. Read the example column — that is what “complete” looks like. Empty boxes come back.</p>
          <div class="dir">Inbound — objects this desk must be able to receive</div>
          ${inbound.length ? renderExplainedHandoffs(inbound, p) : `<p class="lede">No inbound objects recorded.</p>`}
          <div class="dir" style="margin-top:16px">Outbound — objects this desk owes others</div>
          ${outbound.length ? renderExplainedHandoffs(outbound, p) : `<p class="lede">No outbound objects recorded.</p>`}
          ${support.length ? `<div class="dir" style="margin-top:16px">Control inbound — budget, people, systems</div>
            <p class="explain">These land on every function. They are not market research. Do not mix them into demand trackers.</p>
            ${renderExplainedHandoffs(support, p, "control")}` : ""}
          ${supportOut.length ? `<div class="dir" style="margin-top:16px">Sent as part of all functions</div>${renderExplainedHandoffs(supportOut, p, "control")}` : ""}
        `;
      }
      if (id === "escalation") {
        return `${banner}<p class="explain">A trigger, a route, an SLA, a record. A chat to a founder is not a path.</p>
          <div class="card" style="padding:0;overflow:auto"><table>
          <thead><tr><th>Trigger</th><th>Route</th><th>SLA</th><th>Record</th></tr></thead>
          <tbody>${os.escalation.map((e) => `<tr><td>${esc(e.trigger)}</td><td>${esc(e.route)}</td><td>${esc(e.sla)}</td><td>${esc(e.record)}</td></tr>`).join("")}</tbody>
        </table></div>`;
      }
      if (id === "improvement") {
        const imp = os.improvement || {};
        return `${banner}
          <div class="pack-intro">
            <img class="pack-intro-art" src="${ART.flow.learn}" alt="Learning returns to Growth">
            <div class="pack-intro-copy">
              <div class="kicker">${esc(imp.cadence || "Review")}</div>
              <h3 class="serif" style="font-size:22px;margin:4px 0">${esc(imp.question || "What should improve?")}</h3>
              <p class="explain">Evidence from this function should change the next cycle — not sit in a folder.</p>
            </div>
          </div>
          <div class="card">
          <p class="lede" style="margin-top:0">Evidence sources</p>
          ${list(imp.sources || [])}
          <p class="lede">Feeds</p>
          <div class="chips">${(imp.loopsTo || []).map((fid) => fid === "all"
            ? `<button class="rec" data-go="#control-span">All functions</button>`
            : `<button class="rec" data-go="#pack-${fid}/improvement">${esc(packName(fid))}</button>`
          ).join("")}</div>
        </div>`;
      }
      if (id === "structure") {
        return `${banner}<div class="grid-2">${(p.nested || []).map((n) => `<div class="card"><h3>${esc(n.name)}</h3>${list(n.items)}</div>`).join("")}</div>
          ${p.distinction ? `<div class="note">${esc(p.distinction)}</div>` : ""}`;
      }
      if (id === "statuses") {
        return `${banner}<p class="explain">Status is a control. A card in two statuses is a leak.</p>
          <div class="chips">${(p.statuses || []).map((s) => `<span class="rec">${esc(s)}</span>`).join("")}</div>`;
      }
      if (id === "lifecycles") {
        return `${banner}<p class="explain">Read each path left to right. Skipping a stage is how objects go missing.</p>
          <div class="grid-2">${(p.lifecycles || []).map((n) => `<div class="card"><h3>${esc(n.label)}</h3>
          <div class="proc-flow">${(n.items || []).map((it, i) => `${i ? `<span class="pipe-arrow">→</span>` : ""}<span class="proc-step"><span class="pn">${i + 1}</span>${esc(it)}</span>`).join("")}</div>
        </div>`).join("")}</div>`;
      }
      if (id === "requests") {
        return `${banner}<p class="explain">Different request types need different SOPs. Mixing them in one pile creates fake urgency.</p>
          <div class="chips">${(p.requestTypes || []).map((s) => `<span class="rec">${esc(s)}</span>`).join("")}</div>`;
      }
      if (id === "boundary") {
        const rows = (p.doesNotOwn || []).map((d) => `<tr><td>${esc(d.item)}</td><td>${esc(d.owner)}</td></tr>`).join("");
        const bw = p.boundaryWith;
        return `
          ${banner}
          ${doesGrid(st)}
          ${p.distinction ? `<div class="note">${esc(p.distinction)}</div>` : ""}
          ${rows ? `<div class="card" style="padding:0;overflow:auto;margin-top:12px"><table><thead><tr><th>This module does not own</th><th>Owned by</th></tr></thead><tbody>${rows}</tbody></table></div>` : ""}
          ${bw ? `<div class="grid-2" style="margin-top:12px">
            <div class="card"><h3>${esc(bw.thisModule.name)}</h3><p>${esc(bw.thisModule.layer)}</p>${list(bw.thisModule.items)}</div>
            <div class="card"><h3>${esc(bw.otherModule.name)}</h3><p>${esc(bw.otherModule.layer)}</p>${list(bw.otherModule.items)}</div>
          </div>` : ""}
        `;
      }
      return banner;
    }

    function renderPack(p) {
      if (p.status !== "complete") return renderPending(p);
      const tabs = packTabs(p);
      const tabBar = tabs.map(([id, label]) =>
        `<button class="tab" data-sec="${id}" type="button">${label}</button>`
      ).join("");
      const sections = tabs.map(([id, label]) => `
        <section class="section" data-sec="${id}" id="sec-${p.id}-${id}">
          <div class="section-kicker">${esc(label)}</div>
          ${sectionHtml(p, id)}
        </section>
      `).join("");
      const st = packStory(p.id);
      const kicker = p.parent ? `${packName(p.parent)} · nested` : `${p.group} · ${p.short}`;
      return `
        <div class="page pack-page" data-pack-page="${p.id}">
          ${pageIntro(packScene(p.id), p.name, kicker, p.name, `
              <p class="lede">${esc(p.question)}</p>
              <p class="explain">${esc(st.inPlain || p.purpose)}</p>
              <div class="meta-row">
                <span class="pill">Role: ${esc(p.role)}</span>
                <span class="pill">Reports to: ${esc(p.reportsTo)}</span>
              </div>`)}
          <div class="tabs" id="packTabs">${tabBar}</div>
          ${sections}
        </div>
      `;
    }

    function lockPackSpy(ms) {
      packSpyIgnoreUntil = Math.max(packSpyIgnoreUntil, Date.now() + ms);
    }

    function setPackTab(id) {
      const main = byId("os-main");
      const tabsBar = main.querySelector(".tabs");
      main.querySelectorAll(".tab[data-sec]").forEach((t) => t.classList.toggle("active", t.dataset.sec === id));
      const active = tabsBar && tabsBar.querySelector(`.tab[data-sec="${id}"]`);
      if (!active || !tabsBar) return;
      const left = active.offsetLeft - (tabsBar.clientWidth - active.offsetWidth) / 2;
      tabsBar.scrollTo({ left: Math.max(0, left) });
    }

    function scrollPackSection(id) {
      const main = byId("os-main");
      const sec = main.querySelector(`.section[data-sec="${id}"]`);
      if (!sec) return;
      const tabsBar = main.querySelector(".tabs");
      const first = main.querySelector(".section[data-sec]");
      let top = 0;
      if (sec !== first) {
        const offset = (tabsBar ? tabsBar.offsetHeight : 54) + 8;
        top = main.scrollTop + sec.getBoundingClientRect().top - main.getBoundingClientRect().top - offset;
      }
      lockPackSpy(150);
      main.scrollTo({ top: Math.max(0, top) });
      setPackTab(id);
    }

    function attachPackSpy(packId, initialTab) {
      if (packSpyAbort) packSpyAbort.abort();
      packSpyAbort = new AbortController();
      const { signal } = packSpyAbort;
      const main = byId("os-main");
      const tabsBar = main.querySelector(".tabs");
      const sections = [...main.querySelectorAll(".section[data-sec]")];
      if (!sections.length) return;
      let ticking = false;
      let current = "";

      function spyLine() {
        return (tabsBar ? tabsBar.getBoundingClientRect().bottom : 72) + 10;
      }

      function activeFromScroll() {
        if (main.scrollTop + main.clientHeight >= main.scrollHeight - 12) {
          return sections[sections.length - 1].dataset.sec;
        }
        const line = spyLine();
        let active = sections[0];
        for (const sec of sections) {
          if (sec.getBoundingClientRect().top <= line) active = sec;
        }
        return active.dataset.sec;
      }

      function apply(id, writeHash) {
        if (!id || current === id) return;
        current = id;
        setPackTab(id);
        if (writeHash) {
          const next = `#pack-${packId}/${id}`;
          if (location.hash !== next) history.replaceState(null, "", next);
        }
      }

      function tick() {
        if (Date.now() < packSpyIgnoreUntil) return;
        apply(activeFromScroll(), true);
      }

      function onMove() {
        if (ticking) return;
        ticking = true;
        requestAnimationFrame(() => {
          tick();
          ticking = false;
        });
      }

      main.addEventListener("scroll", onMove, { passive: true, signal });
      main.addEventListener("wheel", onMove, { passive: true, signal });
      main.addEventListener("touchmove", onMove, { passive: true, signal });

      const io = new IntersectionObserver(() => onMove(), {
        root: main,
        rootMargin: "-80px 0px -55% 0px",
        threshold: [0, 0.15, 0.5, 1]
      });
      sections.forEach((sec) => io.observe(sec));
      signal.addEventListener("abort", () => io.disconnect());

      main.querySelectorAll(".tab[data-sec]").forEach((btn) => {
        btn.addEventListener("click", () => {
          const id = btn.dataset.sec;
          scrollPackSection(id);
          apply(id, true);
        }, { signal });
      });

      const start = initialTab && sections.some((s) => s.dataset.sec === initialTab)
        ? initialTab
        : sections[0].dataset.sec;
      scrollPackSection(start);
      apply(start, false);
    }

    function render() {
      const raw = (location.hash || "#overview").slice(1);
      const [view, tabRaw] = raw.split("/");
      const tab = aliasTab(tabRaw || "purpose");

      if (view.startsWith("pack-") && renderedKey === view && byId("os-main").querySelector(".pack-page")) {
        scrollPackSection(tab);
        return;
      }

      let html = "";
      if (view === "connections") html = renderConnections();
      else if (view === "cycle") html = renderCycle();
      else if (view === "handoffs") html = renderHandoffsPage(tabRaw);
      else if (view === "control-span") html = renderControlSpan();
      else if (view === "ops-head") html = renderOpsHead();
      else if (view && view.startsWith("group-")) html = renderGroup(view.slice(6));
      else if (view && view.startsWith("pack-")) {
        const p = packById(view.slice(5));
        html = p ? renderPack(p) : `<div class="page empty">Unknown module.</div>`;
      } else html = renderOverview();

      if (packSpyAbort) { packSpyAbort.abort(); packSpyAbort = null; }
      renderedKey = view;
      byId("os-main").innerHTML = html;
      if (!view.startsWith("pack-")) byId("os-main").scrollTop = 0;
      byId("os-progress-chip").textContent = "Master OS";
      renderSidebar();

      byId("os-main").querySelectorAll("[data-go]").forEach((el) => el.addEventListener("click", () => go(el.dataset.go)));
      if (view === "handoffs") bindHandoffPage(tabRaw);
      byId("os-main").querySelectorAll("[data-expand]").forEach((el) => {
        el.addEventListener("click", () => {
          byId("os-main").querySelectorAll("details").forEach((d) => { d.open = el.dataset.expand === "1"; });
        });
      });
      if (view.startsWith("pack-") && byId("os-main").querySelector(".pack-page")) {
        attachPackSpy(view.slice(5), tab);
      }
    }

    function searchIndex() {
      const rows = [];
      DATA.packs.forEach((p) => {
        rows.push({ title: p.name, sub: p.short + " · " + (p.question || ""), go: "#pack-" + p.id + "/purpose" });
        if (p.status !== "complete") return;
        (p.sops || []).forEach((s) => rows.push({ title: s.id + " " + s.title, sub: p.name + " · SOP", go: "#pack-" + p.id + "/sops" }));
        (p.kpis || []).forEach((k) => rows.push({ title: k.name, sub: p.name + " · KPI · " + k.target, go: "#pack-" + p.id + "/kpis" }));
        (p.records || []).forEach((r) => rows.push({ title: r, sub: p.name + " · record", go: "#pack-" + p.id + "/forms" }));
        (p.responsibilities || []).forEach((r) => rows.push({ title: r.letter + ". " + r.title, sub: p.name + " · responsibility", go: "#pack-" + p.id + "/responsibilities" }));
        (osMod(p.id).processes || []).forEach((pr) => rows.push({ title: pr.name, sub: p.name + " · process", go: "#pack-" + p.id + "/processes" }));
        (osMod(p.id).checklists || []).forEach((c) => rows.push({ title: c.name, sub: p.name + " · checklist", go: "#pack-" + p.id + "/checklists" }));
      });
      (OS.handoffs || []).forEach((h) => {
        const lane = handoffLaneOf(h.id);
        rows.push({
          title: h.object,
          sub: (h.plain || packName(h.from) + " → " + packName(h.to)) + (lane ? " · " + lane.name : ""),
          go: lane ? "#handoffs/" + lane.id : "#handoffs"
        });
      });
      DATA.connections.forEach((c) => rows.push({ title: c.title, sub: c.kind === "boundary" ? "Critical system boundary" : "System connection", go: "#connections" }));
      DATA.groups.forEach((g) => {
        const gs = STORY.groups[g.id] || {};
        rows.push({ title: g.name, sub: (gs.line || g.purpose) + " · branch", go: "#group-" + g.id });
      });
      rows.push({ title: "Head of Operations", sub: "Operations Function job description · role, KPIs, SOPs, reports", go: "#ops-head" });
      const head = STORY.heads && STORY.heads.operations;
      if (head) {
        (head.sops || []).forEach((s) => rows.push({ title: s.id + " " + s.title, sub: "Head of Operations · SOP", go: "#ops-head" }));
        (head.kpis || []).forEach((k) => rows.push({ title: k.name, sub: "Head of Operations · KPI · " + k.target, go: "#ops-head" }));
        (head.documents || []).forEach((d) => rows.push({ title: d.name, sub: "Head of Operations · document", go: "#ops-head" }));
      }
      rows.push({ title: "Operating cycle", sub: "Complete Panun Kaergar workflow", go: "#cycle" });
      rows.push({ title: "Handoffs", sub: "Work objects between functions", go: "#handoffs" });
      rows.push({ title: "Control span", sub: "Finance, HR, Technology support all functions", go: "#control-span" });
      return rows;
    }
    const INDEX = searchIndex();

    function runSearch(q) {
      const box = byId("os-results");
      const query = q.trim().toLowerCase();
      if (!query) { box.classList.remove("open"); box.innerHTML = ""; return; }
      const hits = INDEX.filter((r) => (r.title + " " + r.sub).toLowerCase().includes(query)).slice(0, 12);
      box.innerHTML = hits.length
        ? hits.map((h, i) => `<button class="search-hit ${i === 0 ? "active" : ""}" data-go="${h.go}"><b>${esc(h.title)}</b><span>${esc(h.sub)}</span></button>`).join("")
        : `<div class="empty">No matches.</div>`;
      box.classList.add("open");
      box.querySelectorAll("[data-go]").forEach((el) => el.addEventListener("click", () => go(el.dataset.go)));
    }

    byId("os-q").addEventListener("input", (e) => runSearch(e.target.value));
    byId("os-q").addEventListener("keydown", (e) => {
      if (e.key === "Enter") {
        const hit = byId("os-results").querySelector(".search-hit");
        if (hit) go(hit.dataset.go);
      }
      if (e.key === "Escape") { byId("os-results").classList.remove("open"); byId("os-q").blur(); }
    });
    document.addEventListener("keydown", (e) => {
      if (e.key !== "/") return;
      const typing = e.target && e.target.closest && e.target.closest("input, textarea, select, [contenteditable='true']");
      if (typing) return;
      e.preventDefault();
      const q = byId("os-q");
      if (!q) return;
      q.focus();
      q.select();
    });
    byId("os-menu-btn").addEventListener("click", () => {
      const open = byId("os-sidebar").classList.toggle("open");
      byId("os-scrim").classList.toggle("on", open);
    });
    byId("os-scrim").addEventListener("click", closeNav);
    window.addEventListener("hashchange", render);
    render();

})();
