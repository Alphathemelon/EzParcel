<?php
include_once 'auth.php';
authorize([1]);
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
            fld_user_name,
            fld_parcel_pic
        FROM tbl_parcel_ezparcel
        ORDER BY fld_parcel_date DESC
    ");
    $stmt->execute();
    $parcels = $stmt->fetchAll(PDO::FETCH_ASSOC);

   $staffStmt = $conn->prepare("
        SELECT fld_user_name
        FROM tbl_user_ezparcel
        WHERE fld_user_level = 1
        ORDER BY fld_user_name
    ");
    $staffStmt->execute();
    $staffList = $staffStmt->fetchAll(PDO::FETCH_COLUMN);
 
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
        <form class="search-form" onsubmit="searchOrder(event);" role="search">
            <input id="keyword" class="search-input" type="search" placeholder="Search Order ID..." autofocus required />
            <button type="submit" class="search-btn">Go</button>    
        </form>
</div>

<div id="parcelList"></div>

<div id="staffModal" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.5);">
    <div style="background:#fff;width:300px;margin:15% auto;padding:20px;border-radius:10px;">
        <h3>Select Staff</h3>

        <select id="staffSelect" style="width:100%;padding:8px;">
            <option value="">-- Select Staff --</option>
        </select>

        <div style="margin-top:15px;text-align:right;">
            <button onclick="closestaffModal()">Cancel</button>
            <button onclick="confirmPaid()">Confirm</button>
        </div>
    </div>
</div>


<script>
    // SEARCH FUNCTION
function searchOrder(event) {
    event.preventDefault();

    const keyword = document.getElementById("keyword").value.trim().toLowerCase();

    if (keyword === "") {
        displayParcels(parcels); // show all if empty
        return;
    }

    const filtered = parcels.filter(p =>
        p.orderid.toLowerCase().includes(keyword) ||
        p.code.toLowerCase().includes(keyword)
    );

    displayParcels(filtered);
}


// ===================================
// CLEAN PHP → JS data conversion
// =================================== 
let parcels = <?php
    $clean = [];

    foreach ($parcels as $p) {
        $clean[] = [
            "code" => $p["fld_parcel_storage"] . $p["fld_parcel_location"],
            "price"   => (float)$p["fld_parcel_amount"],
            "name"   => $p["fld_user_name"],
            "orderid" => $p["fld_parcel_ID"],
            "weight"  => $p["fld_parcel_weight"],
            "color"   => ($p["fld_parcel_status"] === "Collected" ? "green" : "red"),
            "date"    => $p["fld_parcel_date"],
            "pic"     => $p["fld_parcel_pic"]
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
                        <span style="font-size:15px;font-weight:500;">RM${p.price.toFixed(2)}</span><br>
                        <span style="font-size:15px;font-weight:500;">${new Date(p.date).toLocaleDateString('en-GB', {day: '2-digit',month: 'short',year: 'numeric'})}</span>
                    </div>
                    <div class="toggle-btn" onclick="toggleDetails(this)">⌄</div>
                </div>

                <div class="details">
                    <b>Name:</b> ${p.name}<br>
                    <b>Order ID:</b> ${p.orderid}<br>
                    <b>Weight:</b> ${p.weight}<br>
                    <b>Location:</b> ${p.code}<br>
                    <b>Parcel Photo:</b><br>
                    ${p.pic ? `
                        <div style="margin:8px 0;">
                            <img src="${p.pic}" 
                            alt="Parcel Image"
                            style="width:100%;max-width:250px;border-radius:8px;">
                        </div>
                    ` : `<i>No image uploaded</i><br>`}
<<<<<<< HEAD
        
=======
                    ${p.color === 'red' ? `
                    <b>Date:</b> ${p.date}
                    <div class="action-buttons" style="margin-top:10px;">
                        <button class="btn btn-paid" onclick="openStaffModal('${p.orderid}')">Paid</button>
                    </div>
                    ` : ''}
>>>>>>> 2aadcb6e068f326065aad54e359c2254ecb381e8
                    
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

// ⭐ REQUIRED GLOBALS
const staffList = <?php echo json_encode($staffList); ?>;
let selectedParcelID = null;

// Open modal
function openStaffModal(parcelID) {
    selectedParcelID = parcelID;

    const select = document.getElementById("staffSelect");
    select.innerHTML = `<option value="">-- Select Staff --</option>`;

    staffList.forEach(name => {
        select.innerHTML += `<option value="${name}">${name}</option>`;
    });

    document.getElementById("staffModal").style.display = "block";
}

// Close modal
function closeStaffModal() {
    document.getElementById("staffModal").style.display = "none";
}

// Confirm paid with selected staff
async function confirmPaid() {
    const staff = document.getElementById("staffSelect").value;
    if (!staff) {
        alert("Please select staff");
        return;
    }

    const form = new URLSearchParams();
    form.append("action", "update");
    form.append("parcelID", selectedParcelID);
    form.append("status", "Collected");
    form.append("completedBy", staff);

    const res = await fetch("parcel_CRUD.php", {
        method: "POST",
        headers: { "Content-Type": "application/x-www-form-urlencoded" },
        body: form.toString()
    });

    const data = await res.json();
    if (data.success) {
        const p = parcels.find(x => x.orderid === selectedParcelID);
        if (p) p.color = "green";
        displayParcels(parcels);
        closeStaffModal();
        alert("Parcel marked as Collected");
    } else {
        alert("Update failed");
    }
}


// First load
displayParcels(parcels);
</script>

</body>
</html>