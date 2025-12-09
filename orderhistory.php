<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="css/stylesearch.css"> 
    <title>Order History</title>

</head>
<body>
    <?php include 'navbar.php'; ?>
    



    <div class="tabs", style="margin-bottom:20px;">
        <div class="tab blue active" onclick="filterParcels('all')">ALL</div>
        <div class="tab green" onclick="filterParcels('green')">COMPLETE</div>
        <div class="tab red" onclick="filterParcels('red')">INCOMPLETE</div>
    </div>

    <div class="search-wrapper">
        <form class="search-form" onsubmit="event.preventDefault();" role="search">
            <label for="search" class="visually-hidden">Search for stuff</label>
            
            <input id="search" class="search-input" type="search" placeholder="Search Order ID..." autofocus required />
            
            <button type="submit" class="search-btn">Go</button>    
        </form>
    </div>

    <div id="parcelList"></div>

<script>
/* ======================== 
   SAMPLE DATA (Replace with PHP later)
=========================== */
let parcels = [
    { code: "S10", price: 2.00, phone: "01133237067", orderid: "MXY36273892", weight: "1.30 KG", color: "green" },
    { code: "S06", price: 2.00, phone: "01922772727", orderid: "A0192277727", weight: "0.80 KG", color: "red" },
    { code: "M04", price: 3.00, phone: "0198282828", orderid: "MXY8277272", weight: "2.0 KG", color: "red" },
    { code: "S04", price: 2.00, phone: "01133237067", orderid: "MXY36273892", weight: "1.30 KG", color: "green" },
    { code: "S23", price: 2.00, phone: "01922772727", orderid: "A0192277727", weight: "0.80 KG", color: "red" },
    { code: "M33", price: 3.00, phone: "0198282828", orderid: "MXY8277272", weight: "2.0 KG", color: "red" }
];

/* ========================
   DISPLAY FUNCTION
=========================== */
function displayParcels(list) {
    const parcelList = document.getElementById("parcelList");
    parcelList.innerHTML = "";

    list.forEach((p, i) => {
        // The classes here (order-card, order-header, toggle-btn, details) 
        // match the CSS perfectly.
        parcelList.innerHTML += `
            <div class="order-card ${p.color}">
                <div class="order-header">
                    <div>${p.code}<br>
                    <span style="font-size:15px;font-weight:500;">RM${p.price.toFixed(2)}</span></div>

                    <div id="arrow-${i}" class="toggle-btn" onclick="toggleDetails(${i})">⌄</div>
                </div>

                <div class="details" id="details-${i}">
                    <b>Phone:</b> ${p.phone}<br>
                    <b>Order ID:</b> ${p.orderid}<br>
                    <b>Weight:</b> ${p.weight}<br>
                </div>
            </div>
        `;
    });
}

/* ========================
   FILTER FUNCTION
=========================== */
function filterParcels(type) {
    // Remove 'active' from all tabs
    document.querySelectorAll(".tab").forEach(t => t.classList.remove("active"));

    if (type === "all") {
        document.querySelector(".tab.blue").classList.add("active");
        displayParcels(parcels);
    } else if (type === "green") {
        document.querySelector(".tab.green").classList.add("active");
        displayParcels(parcels.filter(p => p.color === "green"));
    } else if (type === "red") {
        document.querySelector(".tab.red").classList.add("active");
        displayParcels(parcels.filter(p => p.color === "red"));
    }
}

/* ========================
   TOGGLE DETAILS
=========================== */
function toggleDetails(id) {
    const detailsBox = document.getElementById("details-" + id);
    const arrowBtn = document.getElementById("arrow-" + id);

    if (detailsBox.style.display === "block") {
        detailsBox.style.display = "none";
        arrowBtn.classList.remove("open");
    } else {
        detailsBox.style.display = "block";
        arrowBtn.classList.add("open");
    }
}

/* LOAD FIRST VIEW */
displayParcels(parcels);
</script>

</body>
</html>