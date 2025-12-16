<?php
include_once 'database.php';

try {
    $conn = new PDO("mysql:host=$servername;dbname=$dbname;charset=utf8mb4", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Get all parcels
    $stmt = $conn->prepare("
        SELECT fld_parcel_ID, fld_parcel_status
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
<title>Parcel Status</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<style>
body { font-family: Arial, sans-serif; background:#f4f6fb; margin:0; }
.container { max-width:600px; margin:30px auto; background:#fff; padding:20px; border-radius:12px; box-shadow:0 4px 12px rgba(0,0,0,0.08); }
h2 { margin-top:0; }
.search-bar { display:flex; gap:10px; margin-bottom:20px; flex-wrap: wrap; }
.search-bar input { flex:1; padding:10px; border-radius:8px; border:1px solid #ccc; }
.search-bar button { padding:10px 14px; border:none; border-radius:8px; background:#1f6fff; color:#fff; cursor:pointer; }
#backBtn { background:#ccc; color:#000; }
#parcelList { list-style:none; padding:0; margin:0; }
#parcelList li { display:flex; justify-content:space-between; padding:12px; border-bottom:1px solid #eee; font-weight:500; }
.badge { padding:4px 12px; border-radius:20px; font-size:13px; font-weight:bold; }
.badge.complete { background:#d4edda; color:#155724; }
.badge.incomplete { background:#f8d7da; color:#721c24; }
</style>
</head>
<body>

<?php include 'navbar.php'; ?>

<div class="container">
    <h2>Parcel Status</h2>

    <div class="search-bar">
        <input type="text" id="trackingInput" placeholder="Enter tracking number">
        <button onclick="searchTracking()"><i class="fa-solid fa-magnifying-glass"></i></button>
        <button onclick="pasteClipboard()"><i class="fa-solid fa-paste"></i></button>
        <button id="backBtn" onclick="backToList()" style="display:none;">Back</button>
    </div>

    <ul id="parcelList">
        <li style="color:#888;">Loading parcels...</li>
    </ul>
</div>

<script>
// PHP → JS
let parcels = <?php echo json_encode($parcels, JSON_UNESCAPED_SLASHES); ?>;

// Map status
function mapStatus(status){
    return status.toLowerCase() === 'collected'
        ? { text: 'Complete', class: 'complete' }
        : { text: 'Incomplete', class: 'incomplete' };
}

// Render parcels
function renderParcels(list){
    const ul = document.getElementById('parcelList');
    ul.innerHTML = '';
    if(!list.length){ 
        ul.innerHTML = '<li style="color:#888;">No parcels found</li>'; 
        return; 
    }

    list.forEach(p=>{
        const status = mapStatus(p.fld_parcel_status);
        ul.innerHTML += `<li>
            <span>${p.fld_parcel_ID}</span>
            <span class="badge ${status.class}">${status.text}</span>
        </li>`;
    });
}

// Search
function searchTracking(){
    const keyword = document.getElementById('trackingInput').value.trim().toLowerCase();
    const backBtn = document.getElementById('backBtn');

    if(!keyword){ 
        renderParcels(parcels); 
        backBtn.style.display = 'none';
        return; 
    }

    const filtered = parcels.filter(p => p.fld_parcel_ID.toLowerCase().includes(keyword));
    renderParcels(filtered);
    backBtn.style.display = 'inline-block';
}

// Paste from clipboard
async function pasteClipboard(){
    try{
        const text = await navigator.clipboard.readText();
        document.getElementById('trackingInput').value = text;
        searchTracking();
    }catch(err){
        alert('Unable to access clipboard');
    }
}

// Back to full list
function backToList(){
    document.getElementById('trackingInput').value = '';
    renderParcels(parcels);
    document.getElementById('backBtn').style.display = 'none';
}

// Initial render
renderParcels(parcels);
</script>

</body>
</html>
