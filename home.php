<?php
session_start();

// Cek sesi admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8" />
    <title>Daftar Keluhan Warga</title>
    <style>
        body {
            font-family: sans-serif;
            background: #f9f9f9;
            padding: 30px;
        }
        h2 {
            margin-bottom: 20px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            background: white;
        }
        th, td {
            border: 1px solid #ddd;
            padding: 12px;
            text-align: left;
            vertical-align: top;
        }
        th {
            background: #f1f1f1;
        }
        .btn-delete {
            color: white;
            background: red;
            padding: 6px 10px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }
        .btn-delete:hover {
            background: darkred;
        }
        img.foto-keluhan {
            max-width: 100px;
            border-radius: 4px;
            display: block;
            margin-top: 5px;
        }
    </style>
</head>
<body>

<h2>Daftar Keluhan Warga</h2>

<table id="keluhan-table">
    <thead>
        <tr>
            <th>No</th>
            <th>Judul & Isi</th>
            <th>Foto</th>
            <th>Tanggal</th>
            <th>Aksi</th>
        </tr>
    </thead>
    <tbody></tbody>
</table>

<script>
async function loadKeluhan() {
    try {
        const response = await fetch('http://localhost/KELUHAN-MASYARAKAT/routes/admin/admin_daftar_berita.php');
        const data = await response.json();
        const tbody = document.querySelector('#keluhan-table tbody');
        tbody.innerHTML = '';

        if (!data.success || !data.data || data.data.length === 0) {
            const row = `<tr><td colspan="5" style="text-align:center">Belum ada keluhan.</td></tr>`;
            tbody.innerHTML = row;
            return;
        }

        data.data.forEach((item, index) => {
            const tr = document.createElement('tr');
            tr.innerHTML = `
                <td>${index + 1}</td>
                <td>
                    <strong>${item.judul}</strong><br>
                    <small>${item.isi.replace(/\n/g, '<br>')}</small>
                </td>
                <td>
                    ${item.foto ? `<img src="${item.foto}" alt="Foto keluhan" class="foto-keluhan">` : '-'}
                </td>
                <td>${item.created_at}</td>
                <td>
                    <button class="btn-delete" onclick="hapusKeluhan(${item.id})">Hapus</button>
                </td>
            `;
            tbody.appendChild(tr);
        });
    } catch (error) {
        console.error('Error loadKeluhan:', error);
        const tbody = document.querySelector('#keluhan-table tbody');
        tbody.innerHTML = `<tr><td colspan="5" style="text-align:center; color:red;">Gagal memuat data keluhan.</td></tr>`;
    }
}

async function hapusKeluhan(id) {
    if (!confirm('Yakin ingin menghapus berita ini?')) return;

    const response = await fetch('http://localhost/KELUHAN-MASYARAKAT/routes/admin/admin_proses_berita.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ action: 'hapus', id: id })
    });

    const result = await response.json();
    if (result.success) {
        alert('berita berhasil dihapus.');
        loadKeluhan();
    } else {
        alert('Gagal menghapus berita: ' + (result.error || 'Terjadi kesalahan'));
    }
}


loadKeluhan();
</script>

</body>
</html>
