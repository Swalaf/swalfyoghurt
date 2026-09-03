(function () {
  "use strict";

  var DATA = JSON.parse(document.getElementById("swalaf-data").textContent);
  var NGN = function (n) { return "₦" + n.toLocaleString("en-NG"); };

  function beacon(url, payload) {
    try {
      var body = JSON.stringify(payload);
      if (navigator.sendBeacon) {
        navigator.sendBeacon(url, new Blob([body], { type: "application/json" }));
      } else {
        fetch(url, { method: "POST", body: body, headers: { "Content-Type": "application/json" }, keepalive: true });
      }
    } catch (e) { /* analytics must never break the page */ }
  }

  function guessDevice() {
    var ua = navigator.userAgent || "";
    if (/iPad|Tablet/i.test(ua)) return "Tablet";
    if (/Mobi|Android|iPhone/i.test(ua)) return "Mobile";
    return "Desktop";
  }

  beacon("api/log-pageview.php", {
    path: location.pathname,
    referrer: document.referrer || "",
    device: guessDevice(),
  });

  document.addEventListener("click", function (e) {
    var el = e.target.closest(".js-outbound");
    if (el) beacon("api/log-click.php", { label: el.dataset.label || el.textContent.trim().slice(0, 80) });
  });

  // ---------------- Loading splash ----------------
  (function () {
    var pct = 0;
    var bar = document.getElementById("splashBar");
    var pctEl = document.getElementById("splashPct");
    var splash = document.getElementById("splash");
    var timer = setInterval(function () {
      pct = Math.min(100, pct + 4);
      if (bar) bar.style.width = pct + "%";
      if (pctEl) pctEl.textContent = pct + "%";
      if (pct >= 100) clearInterval(timer);
    }, 88);
    setTimeout(function () {
      if (splash) splash.style.setProperty("display", "none");
    }, 3000);
  })();

  // ---------------- Hero slideshow ----------------
  (function () {
    var slides = DATA.slides || [];
    if (!slides.length) return;
    var imgs = document.querySelectorAll("#heroSlides img");
    var dots = document.querySelectorAll("#heroDots button");
    var kickerEl = document.getElementById("slideKicker");
    var labelEl = document.getElementById("slideLabel");
    var current = 0;

    function show(i) {
      current = i;
      imgs.forEach(function (img, k) { img.classList.toggle("active", k === i); });
      dots.forEach(function (d, k) { d.classList.toggle("active", k === i); });
      if (kickerEl) kickerEl.textContent = slides[i].kicker;
      if (labelEl) labelEl.textContent = slides[i].label;
    }

    dots.forEach(function (d, i) {
      d.addEventListener("click", function () { show(i); });
    });

    setInterval(function () { show((current + 1) % slides.length); }, 5200);
  })();

  // ---------------- Price list: category filter + size pick + add to tray ----------------
  var order = []; // [{key, name, size, unit, qty}]

  var productsBySlug = {};
  (DATA.products || []).forEach(function (p) { productsBySlug[p.id] = p; });

  (function () {
    var chips = document.querySelectorAll("#catChips .chip");
    var cards = document.querySelectorAll("#productGrid .product-card");
    chips.forEach(function (chip) {
      chip.addEventListener("click", function () {
        chips.forEach(function (c) { c.classList.remove("active"); });
        chip.classList.add("active");
        var cat = chip.dataset.cat;
        cards.forEach(function (card) {
          card.style.display = (cat === "All" || card.dataset.cat === cat) ? "" : "none";
        });
      });
    });
  })();

  function noteFor(product, size) {
    if (size.min > 1) return "minimum order " + size.min;
    return "per " + (product.cat === "Kids" || product.cat === "Zobo" ? "pack" : "unit");
  }

  document.querySelectorAll("#productGrid .product-card").forEach(function (card) {
    var slug = card.dataset.product;
    var product = productsBySlug[slug];
    var sizeBtns = card.querySelectorAll(".size-btn");
    var priceEl = card.querySelector(".price");
    var noteEl = card.querySelector(".note");
    var addBtn = card.querySelector(".btn-add");
    var selectedIndex = 0;

    function selectSize(i) {
      selectedIndex = i;
      sizeBtns.forEach(function (b, k) { b.classList.toggle("active", k === i); });
      var s = product.sizes[i];
      priceEl.textContent = NGN(s.price);
      noteEl.textContent = noteFor(product, s);
    }

    sizeBtns.forEach(function (btn, i) {
      btn.addEventListener("click", function () { selectSize(i); });
    });

    addBtn.addEventListener("click", function () {
      var s = product.sizes[selectedIndex];
      var key = product.id + "|" + s.label;
      var existing = order.find(function (o) { return o.key === key; });
      if (existing) {
        existing.qty += 1;
      } else {
        order.push({ key: key, name: product.name, size: s.label, unit: s.price, qty: s.min || 1 });
      }
      renderTray();
    });
  });

  // ---------------- Order tray ----------------
  var tray = document.getElementById("tray");
  var trayItems = document.getElementById("trayItems");
  var trayCount = document.getElementById("trayCount");
  var trayTotal = document.getElementById("trayTotal");
  var trayWaLink = document.getElementById("trayWaLink");

  function bump(key, delta) {
    var item = order.find(function (o) { return o.key === key; });
    if (!item) return;
    item.qty += delta;
    if (item.qty <= 0) order = order.filter(function (o) { return o.key !== key; });
    renderTray();
  }

  function renderTray() {
    if (!order.length) {
      tray.hidden = true;
      return;
    }
    tray.hidden = false;
    var totalQty = order.reduce(function (t, o) { return t + o.qty; }, 0);
    var total = order.reduce(function (t, o) { return t + o.unit * o.qty; }, 0);
    trayCount.textContent = "Your tray · " + totalQty + " item" + (totalQty === 1 ? "" : "s");
    trayTotal.textContent = NGN(total);

    trayItems.innerHTML = "";
    order.forEach(function (o) {
      var row = document.createElement("div");
      row.className = "tray-item";
      row.innerHTML =
        '<div style="flex:1;min-width:0">' +
          '<div class="name">' + o.name + '</div>' +
          '<div class="meta">' + o.size + ' · ' + NGN(o.unit) + '</div>' +
        '</div>' +
        '<div style="display:flex;align-items:center;gap:8px">' +
          '<button type="button" class="qty-btn" data-act="dec">−</button>' +
          '<span style="font-size:13.5px;font-weight:700;min-width:14px;text-align:center">' + o.qty + '</span>' +
          '<button type="button" class="qty-btn" data-act="inc">+</button>' +
        '</div>';
      row.querySelector('[data-act="dec"]').addEventListener("click", function () { bump(o.key, -1); });
      row.querySelector('[data-act="inc"]').addEventListener("click", function () { bump(o.key, 1); });
      trayItems.appendChild(row);
    });

    var lines = order.map(function (o) { return "• " + o.name + " — " + o.size + " × " + o.qty + " (" + NGN(o.unit * o.qty) + ")"; }).join("\n");
    var msg = "Hello Swalaf! I'd like to order:\n" + lines + "\n\nEstimated total: " + NGN(total) + "\nPreferred delivery date: ";
    trayWaLink.href = "https://wa.me/" + DATA.waOrders.replace(/[^0-9]/g, "") + "?text=" + encodeURIComponent(msg);
  }

  document.getElementById("trayClear").addEventListener("click", function () {
    order = [];
    renderTray();
  });

  trayWaLink.addEventListener("click", function () {
    beacon("api/log-order.php", {
      kind: "order",
      items: order.map(function (o) { return { name: o.name, size: o.size, qty: o.qty, unit: o.unit }; }),
      total: order.reduce(function (t, o) { return t + o.unit * o.qty; }, 0),
    });
  });

  // ---------------- Events calculator ----------------
  (function () {
    var guestChips = document.querySelectorAll("#guestChips .chip");
    var styleChips = document.querySelectorAll("#eventStyleChips .size-btn");
    var planEl = document.getElementById("eventPlan");
    var linkEl = document.getElementById("eventWaLink");
    if (!planEl) return;

    var guests = 50;
    var style = "Drinks + parfait";

    function packs(n) { return Math.max(1, Math.ceil(n / 6)); }

    function eventLines() {
      var g = guests, out = [];
      if (style === "Drinks only") {
        out.push({ q: Math.round(g * 0.6), unit: 2000, label: "Plain Yoghurt 35cl", suffix: "bottles" });
        out.push({ q: packs(g * 0.4), unit: 4600, label: "Fruity Zobo 35cl", suffix: "packs of 6" });
      } else if (style === "Full spread") {
        out.push({ q: Math.round(g * 0.5), unit: 2000, label: "Plain Yoghurt 35cl", suffix: "bottles" });
        out.push({ q: packs(g * 0.5), unit: 4600, label: "Fruity Zobo 35cl", suffix: "packs of 6" });
        out.push({ q: Math.round(g * 0.6), unit: 3900, label: "Parfait 250ml", suffix: "cups" });
        out.push({ q: Math.max(1, Math.round(g / 20)), unit: 9500, label: "Greek Yoghurt 1 litre", suffix: "tubs" });
      } else {
        out.push({ q: Math.round(g * 0.5), unit: 2000, label: "Plain Yoghurt 35cl", suffix: "bottles" });
        out.push({ q: Math.round(g * 0.5), unit: 3900, label: "Parfait 250ml", suffix: "cups" });
        out.push({ q: packs(g * 0.3), unit: 4600, label: "Fruity Zobo 35cl", suffix: "packs of 6" });
      }
      return out;
    }

    function render() {
      guestChips.forEach(function (c) { c.classList.toggle("active", Number(c.dataset.guests) === guests); });
      styleChips.forEach(function (c) { c.classList.toggle("active", c.dataset.style === style); });

      var lines = eventLines();
      planEl.innerHTML = "";
      lines.forEach(function (l) {
        var row = document.createElement("div");
        row.className = "plan-line";
        row.innerHTML = '<span class="bullet"></span><span class="item">' + l.q + " " + l.suffix + " · " + l.label + "</span>";
        planEl.appendChild(row);
      });

      var evMsg = "Hello Swalaf! I'd like a quote for an event.\nGuests: ~" + guests + "\nStyle: " + style +
        "\n\nSuggested spread:\n" + lines.map(function (l) { return "• " + l.q + " " + l.suffix + " — " + l.label; }).join("\n") +
        "\n\nEvent date: \nDelivery area: ";
      linkEl.href = "https://wa.me/" + DATA.waEvents.replace(/[^0-9]/g, "") + "?text=" + encodeURIComponent(evMsg);
    }

    guestChips.forEach(function (c) {
      c.addEventListener("click", function () { guests = Number(c.dataset.guests); render(); });
    });
    styleChips.forEach(function (c) {
      c.addEventListener("click", function () { style = c.dataset.style; render(); });
    });

    linkEl.addEventListener("click", function () {
      var lines = eventLines();
      beacon("api/log-order.php", {
        kind: "event_quote",
        items: lines.map(function (l) { return { name: l.label, size: l.suffix, qty: l.q, unit: l.unit }; }),
        total: lines.reduce(function (t, l) { return t + l.unit * l.q; }, 0),
        note: guests + " guests · " + style,
      });
    });

    render();
  })();

  // ---------------- Chat widget ----------------
  (function () {
    var panel = document.getElementById("chatPanel");
    var toggle = document.getElementById("chatToggle");
    var label = document.getElementById("chatToggleLabel");
    if (!toggle) return;
    toggle.addEventListener("click", function () {
      var open = panel.hidden;
      panel.hidden = !open;
      label.textContent = open ? "Close chat" : "Chat with us";
    });
  })();
})();
