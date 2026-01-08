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
            fld_parcel_pic,
            fld_completed_by
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
    <link rel="icon" href="images/logo.png" type="image/png">
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
            "completedBy" => $p["fld_completed_by"] ?? "",
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
                    <b>Completed by:</b> ${p.completedBy ? p.completedBy : "<i>Not yet collected</i>"}<br>
                     
                   <span style="font-weight:bold;color:${p.color === "green" ? "green" : "red"};">
                        Status: ${p.color === "green" ? "Collected" : "Not Collected"}
                   </span>

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




// First load
displayParcels(parcels);
</script>

</body>
</html>