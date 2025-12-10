<?php
include_once 'database.php';

try {
    // Connect
    $conn = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Read all parcels
    $stmt = $conn->prepare("
        SELECT 
            fld_parcel_ID,
            fld_parcel_status,
            fld_parcel_storage,
            fld_parcel_date,
            fld_parcel_amount,
            fld_parcel_weight,
            fld_parcel_location,
            fld_user_phone
        FROM tbl_parcel_ezparcel
        ORDER BY fld_parcel_date DESC
    ");
    $stmt->execute();
    $parcels = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    die("DB Error: " . $e->getMessage());
}

$conn = null;
?>

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

<div class="tabs" style="margin-bottom:20px;">
    <div class="tab blue active" onclick="filterParcels('all')">ALL</div>
    <div class="tab green" onclick="filterParcels('green')">COMPLETE</div>
    <div class="tab red" onclick="filterParcels('red')">INCOMPLETE</div>
</div>

<div class="search-wrapper">
    <form class="search-form" onsubmit="event.preventDefault();" role="search">
        <input id="code" class="search-input" type="search" placeholder="Search Order ID..." autofocus required />
        <button type="submit" class="search-btn">Go</button>    
    </form>
</div>

<div id="parcelList"></div>

<script>
    // SEARCH FUNCTION
document.getElementById("code").addEventListener("input", function () {
    let keyword = this.value.trim().toLowerCase();

    if (keyword === "") {
        displayParcels(parcels);
        return;
    }

    let filtered = parcels.filter(p =>
        p.code.toLowerCase().includes(keyword)
    );

    displayParcels(filtered);
});

// ===================================
// CLEAN PHP → JS data conversion
// =================================== 
let parcels = <?php
    $clean = [];

    foreach ($parcels as $p) {
        $clean[] = [
            "code" => $p["fld_parcel_storage"] . $p["fld_parcel_location"],
            "price"   => (float)$p["fld_parcel_amount"],
            "phone"   => $p["fld_user_phone"],
            "orderid" => $p["fld_parcel_ID"],
            "weight"  => $p["fld_parcel_weight"],
            "color"   => ($p["fld_parcel_status"] === "Collected" ? "green" : "red"),
            "date"    => $p["fld_parcel_date"]
        ];
    }

    echo json_encode($clean, JSON_UNESCAPED_SLASHES);
?>;


// ===================================
// DISPLAY LIST
// ===================================
function displayParcels(list) {
    const parcelList = document.getElementById("parcelList");
    parcelList.innerHTML = "";
    list.forEach((p, i) => {
        parcelList.innerHTML += `
            <div class="order-card ${p.color}">
                <div class="order-header">
                    <div>${p.code}<br>
                        <span style="font-size:15px;font-weight:500;">RM${p.price.toFixed(2)}</span>
                    </div>
                    <div class="toggle-btn" onclick="toggleDetails(this)">⌄</div>
                </div>

                <div class="details">
                    <b>Phone:</b> ${p.phone}<br>
                    <b>Order ID:</b> ${p.orderid}<br>
                    <b>Weight:</b> ${p.weight}<br>
                    ${p.color === 'red' ? `
                    <b>Date:</b> ${p.date}
                    <div class="action-buttons" style="margin-top:10px;">
                        <button class="btn btn-paid" onclick="markPaid(this, '${p.orderid}')">Paid</button>
                    </div>
                    ` : ''}
                    
                </div>
            </div>
        `;
    });
    // displayParcels complete
}


// FILTER
function filterParcels(type) {
    document.querySelectorAll(".tab").forEach(t => t.classList.remove("active"));

    if (type === "all") {
        document.querySelector(".tab.blue").classList.add("active");
        displayParcels(parcels);
    } 
    else if (type === "green") {
        // GREEN = Collected
        document.querySelector(".tab.green").classList.add("active");
        displayParcels(parcels.filter(p => p.color === "green"));
    } 
    else if (type === "red") {
        // RED = Uncollected
        document.querySelector(".tab.red").classList.add("active");
        displayParcels(parcels.filter(p => p.color === "red"));
    }
}



// TOGGLE
function toggleDetails(el) {
    const card = el.closest('.order-card');
    if (!card) return;
    // Close other open cards so only one is open at a time
    document.querySelectorAll('.order-card.open').forEach(c => {
        if (c === card) return;
        c.classList.remove('open');
        const btn = c.querySelector('.toggle-btn');
        if (btn) btn.classList.remove('open');
    });

    // Toggle the clicked card
    card.classList.toggle('open');
    el.classList.toggle('open');
}


// Mark parcel as Paid (Collected)
async function markPaid(btn, parcelID) {
    // Ask for confirmation before proceeding
    const ok = confirm(`Mark parcel ${parcelID} as Paid/Collected?`);
    if (!ok) return;

    try {
        btn.disabled = true;
        btn.textContent = 'Saving...';

        const form = new URLSearchParams();
        form.append('action', 'update');
        form.append('parcelID', parcelID);
        form.append('status', 'Collected');

        const res = await fetch('parcel_CRUD.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: form.toString()
        });

        const data = await res.json();
        if (res.ok && data.success) {
            // Update UI: change card color to green and close details
            const card = btn.closest('.order-card');
            if (card) {
                card.classList.remove('red');
                card.classList.add('green');
                card.classList.remove('open');
                const toggle = card.querySelector('.toggle-btn');
                if (toggle) toggle.classList.remove('open');
            }

            // Update in-memory parcels array so filters remain consistent
            const idx = parcels.findIndex(p => p.orderid === parcelID);
            if (idx !== -1) parcels[idx].color = 'green';

            alert('Parcel marked as Paid/Collected');
        } else {
            alert('Failed to mark parcel: ' + (data.error || data.message || 'Unknown error'));
            btn.disabled = false;
            btn.textContent = 'Paid';
        }
    } catch (err) {
        console.error(err);
        alert('Network error while updating parcel');
        btn.disabled = false;
        btn.textContent = 'Paid';
    }
}


// First load
displayParcels(parcels);
</script>

</body>
</html>
