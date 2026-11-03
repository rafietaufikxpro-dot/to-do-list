# Kontrak API - To Do List Duo

## 1. Informasi Tim

| Item | Detail |
|---|---|
| Nama BE | [Isi nama backend developer] |
| Nama FE | [Isi nama frontend developer] |
| Kelas | [Isi kelas] |
| Tanggal | [Isi tanggal] |

## 2. Misi Proyek

Website To-Do List berfungsi untuk membantu pengguna mencatat, mengelola, dan menyelesaikan tugas harian. Backend dan frontend harus berkomunikasi melalui kontrak API yang jelas agar proses integrasi dapat berjalan tanpa hambatan.

## 3. Tujuan API

- Menyediakan data tugas secara konsisten
- Memungkinkan CRUD task dari frontend
- Menjaga struktur response yang terstandarisasi
- Memudahkan integrasi antara backend, frontend, dan mock data

## 4. Base URL

```text
http://localhost:8000/api
```

## 5. Format Umum

- Semua request menggunakan JSON
- Header wajib:

```http
Content-Type: application/json
Accept: application/json
```

- Semua response menggunakan format JSON standar
- Tidak menggunakan bentuk response HTML pada endpoint API

## 6. Standar Response

### Response sukses

```json
{
  "success": true,
  "message": "Data berhasil diambil",
  "data": {}
}
```

### Response gagal

```json
{
  "success": false,
  "message": "Validasi gagal",
  "errors": {
    "title": ["Judul tugas wajib diisi"]
  }
}
```

## 7. Kode Status HTTP

| Kode | Arti |
|---|---|
| 200 | OK |
| 201 | Created |
| 204 | No Content |
| 400 | Bad Request |
| 401 | Unauthorized |
| 404 | Not Found |
| 409 | Conflict |
| 422 | Validation Error |
| 500 | Internal Server Error |

## 8. Struktur Data Tugas

```json
{
  "id": 1,
  "title": "Mengerjakan tugas Laravel",
  "description": "Menyiapkan fitur CRUD task dan validasi",
  "status": "pending",
  "priority": "high",
  "due_date": "2026-11-05",
  "created_at": "2026-11-03T09:52:22.000000Z",
  "updated_at": "2026-11-03T09:52:22.000000Z"
}
```

### Field deskripsi

| Field | Tipe | Wajib | Deskripsi |
|---|---|---:|---|
| id | integer | Ya | ID unik tugas |
| title | string | Ya | Judul tugas |
| description | string/null | Tidak | Deskripsi detail tugas |
| status | string | Ya | `pending`, `in_progress`, `completed` |
| priority | string | Ya | `low`, `medium`, `high` |
| due_date | string/null | Tidak | Tanggal deadline dalam format `YYYY-MM-DD` |
| created_at | datetime | Ya | Waktu pembuatan |
| updated_at | datetime | Ya | Waktu terakhir update |

## 9. Endpoint API

### 9.1 Ambil semua tugas

```http
GET /tasks
```

#### Response contoh

```json
{
  "success": true,
  "message": "Daftar tugas berhasil diambil",
  "data": [
    {
      "id": 1,
      "title": "Mengerjakan tugas Laravel",
      "description": "Menyiapkan fitur CRUD task dan validasi",
      "status": "pending",
      "priority": "high",
      "due_date": "2026-11-05",
      "created_at": "2026-11-03T09:52:22.000000Z",
      "updated_at": "2026-11-03T09:52:22.000000Z"
    }
  ]
}
```

---

### 9.2 Ambil detail tugas

```http
GET /tasks/{id}
```

#### Response contoh

```json
{
  "success": true,
  "message": "Detail tugas berhasil diambil",
  "data": {
    "id": 1,
    "title": "Mengerjakan tugas Laravel",
    "description": "Menyiapkan fitur CRUD task dan validasi",
    "status": "pending",
    "priority": "high",
    "due_date": "2026-11-05",
    "created_at": "2026-11-03T09:52:22.000000Z",
    "updated_at": "2026-11-03T09:52:22.000000Z"
  }
}
```

#### Error contoh

```json
{
  "success": false,
  "message": "Tugas tidak ditemukan",
  "errors": {}
}
```

---

### 9.3 Membuat tugas baru

```http
POST /tasks
```

#### Request body

```json
{
  "title": "Belajar API Laravel",
  "description": "Membuat dokumentasi dan mencoba endpoint baru",
  "status": "pending",
  "priority": "medium",
  "due_date": "2026-11-06"
}
```

#### Response contoh (201)

```json
{
  "success": true,
  "message": "Tugas berhasil dibuat",
  "data": {
    "id": 2,
    "title": "Belajar API Laravel",
    "description": "Membuat dokumentasi dan mencoba endpoint baru",
    "status": "pending",
    "priority": "medium",
    "due_date": "2026-11-06",
    "created_at": "2026-11-03T10:00:00.000000Z",
    "updated_at": "2026-11-03T10:00:00.000000Z"
  }
}
```

#### Validasi

- `title` wajib diisi
- `status` harus salah satu: `pending`, `in_progress`, `completed`
- `priority` harus salah satu: `low`, `medium`, `high`
- `due_date` jika diisi harus format `YYYY-MM-DD`

---

### 9.4 Mengubah tugas

```http
PUT /tasks/{id}
```

#### Request body

```json
{
  "title": "Belajar API Laravel",
  "description": "Membuat dokumentasi dan mencoba endpoint baru",
  "status": "in_progress",
  "priority": "high",
  "due_date": "2026-11-07"
}
```

#### Response contoh

```json
{
  "success": true,
  "message": "Tugas berhasil diperbarui",
  "data": {
    "id": 2,
    "title": "Belajar API Laravel",
    "description": "Membuat dokumentasi dan mencoba endpoint baru",
    "status": "in_progress",
    "priority": "high",
    "due_date": "2026-11-07",
    "created_at": "2026-11-03T10:00:00.000000Z",
    "updated_at": "2026-11-03T10:30:00.000000Z"
  }
}
```

> Alternatif yang sering dipakai: `PATCH /tasks/{id}` untuk update sebagian field. Jika tim memilih partial update, maka kontrak ini tetap berlaku dengan aturan field yang dikirim bersifat opsional.

---

### 9.5 Menghapus tugas

```http
DELETE /tasks/{id}
```

#### Response contoh

```json
{
  "success": true,
  "message": "Tugas berhasil dihapus"
}
```

---

## 10. Aturan Integrasi Frontend dan Backend

1. Backend bertanggung jawab atas validasi, penyimpanan, dan response yang konsisten.
2. Frontend wajib menampilkan loading state saat request sedang diproses.
3. Frontend harus menangani error dari API dengan menampilkan pesan yang ramah pengguna.
4. Semua action pada tugas harus mengikuti kontrak response di atas.
5. Jika ada perubahan pada struktur response, maka harus dicek ulang bersama tim sebelum diterapkan.

## 11. Catatan Implementasi

- Untuk MVP, autentikasi dapat diabaikan jika belum dibahas di sprint awal.
- Jika project berkembang, endpoint authentication seperti `/login` dan `/register` dapat ditambahkan dengan kontrak yang terpisah.
- Untuk dokumentasi lanjutan, tim dapat mengonversi file ini ke format OpenAPI/Swagger.

## 12. Kesimpulan

Kontrak API ini menjadi sumber referensi utama antara backend dan frontend. Dengan kesepakatan yang jelas, proses pengerjaan dapat dilakukan paralel tanpa menunggu satu sama lain, sesuai dengan alur kerja proyek To-Do List Duo.
