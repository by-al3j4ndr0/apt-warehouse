const API_URL = "../api/manageDriversVehicules.php";

const modal     = document.getElementById("modal");
const modalBox  = document.getElementById("modal-box");

const driverSelect   = document.getElementById("driver");
const vehiculeSelect = document.getElementById("vehicule");

// ============ HELPERS ============
function openModal(html) {
    modalBox.innerHTML = html;
    modal.classList.add("show");
}

function closeModal() {
    modal.classList.remove("show");
    modalBox.innerHTML = "";
}

function showError(msg) {
    alert("Error: " + msg);
}

async function apiRequest(action, data = {}) {
    const payload = new URLSearchParams({ action, ...data });
    const res = await fetch(API_URL, {
        method: "POST",
        headers: { "Content-Type": "application/x-www-form-urlencoded" },
        body: payload
    });
    const json = await res.json();
    if (!json.success) throw new Error(json.message || "Error desconocido");
    return json;
}

// ============ MODALES DINÁMICOS ============

// ---------- CHOFER: agregar ----------
function openAddDriverModal() {
    openModal(`
        <button class="modal-close" onclick="closeModal()">&times;</button>
        <h2>Agregar Chofer</h2>
        <form id="driverForm">
            <div class="mb-2">
                <label class="form-label">Nombre</label>
                <input type="text" class="form-control" name="name" required>
            </div>
            <div class="mb-2">
                <label class="form-label">CI</label>
                <input type="text" class="form-control" name="ci" required>
            </div>
            <div class="d-flex justify-content-end gap-2 mt-3">
                <button type="button" class="btn btn-secondary" onclick="closeModal()">Cancelar</button>
                <button type="submit" class="btn btn-primary">Guardar</button>
            </div>
        </form>
    `);
    document.getElementById("driverForm").addEventListener("submit", async (e) => {
        e.preventDefault();
        const fd = new FormData(e.target);
        try {
            await apiRequest("addDriver", { name: fd.get("name"), ci: fd.get("ci") });
            location.reload();
        } catch (err) { showError(err.message); }
    });
}

// ---------- CHOFER: editar ----------
function openEditDriverModal() {
    const opt = driverSelect.options[driverSelect.selectedIndex];
    if (!opt || !opt.value) { alert("Seleccione un chofer para editar"); return; }

    openModal(`
        <button class="modal-close" onclick="closeModal()">&times;</button>
        <h2>Editar Chofer</h2>
        <form id="driverForm">
            <input type="hidden" name="id" value="${opt.value}">
            <div class="mb-2">
                <label class="form-label">Nombre</label>
                <input type="text" class="form-control" name="name" value="${opt.dataset.name}" required>
            </div>
            <div class="mb-2">
                <label class="form-label">CI</label>
                <input type="text" class="form-control" name="ci" value="${opt.dataset.ci}" required>
            </div>
            <div class="d-flex justify-content-end gap-2 mt-3">
                <button type="button" class="btn btn-secondary" onclick="closeModal()">Cancelar</button>
                <button type="submit" class="btn btn-primary">Actualizar</button>
            </div>
        </form>
    `);
    document.getElementById("driverForm").addEventListener("submit", async (e) => {
        e.preventDefault();
        const fd = new FormData(e.target);
        try {
            await apiRequest("editDriver", {
                id: fd.get("id"), name: fd.get("name"), ci: fd.get("ci")
            });
            location.reload();
        } catch (err) { showError(err.message); }
    });
}

// ---------- CHOFER: eliminar ----------
function openRemoveDriverModal() {
    const opt = driverSelect.options[driverSelect.selectedIndex];
    if (!opt || !opt.value) { alert("Seleccione un chofer para eliminar"); return; }

    openModal(`
        <button class="modal-close" onclick="closeModal()">&times;</button>
        <h2>Eliminar Chofer</h2>
        <p>¿Está seguro que desea eliminar a <strong>${opt.dataset.name}</strong>?</p>
        <form id="driverForm">
            <input type="hidden" name="id" value="${opt.value}">
            <div class="d-flex justify-content-end gap-2 mt-3">
                <button type="button" class="btn btn-secondary" onclick="closeModal()">Cancelar</button>
                <button type="submit" class="btn btn-danger">Eliminar</button>
            </div>
        </form>
    `);
    document.getElementById("driverForm").addEventListener("submit", async (e) => {
        e.preventDefault();
        const fd = new FormData(e.target);
        try {
            await apiRequest("removeDriver", { id: fd.get("id") });
            location.reload();
        } catch (err) { showError(err.message); }
    });
}

// ---------- VEHICULO: agregar ----------
function openAddVehiculeModal() {
    openModal(`
        <button class="modal-close" onclick="closeModal()">&times;</button>
        <h2>Agregar Vehículo</h2>
        <form id="vehiculeForm">
            <div class="mb-2">
                <label class="form-label">Matrícula</label>
                <input type="text" class="form-control" name="matriculate" required>
            </div>
            <div class="d-flex justify-content-end gap-2 mt-3">
                <button type="button" class="btn btn-secondary" onclick="closeModal()">Cancelar</button>
                <button type="submit" class="btn btn-primary">Guardar</button>
            </div>
        </form>
    `);
    document.getElementById("vehiculeForm").addEventListener("submit", async (e) => {
        e.preventDefault();
        const fd = new FormData(e.target);
        try {
            await apiRequest("addVehicule", { matriculate: fd.get("matriculate") });
            location.reload();
        } catch (err) { showError(err.message); }
    });
}

// ---------- VEHICULO: editar ----------
function openEditVehiculeModal() {
    const opt = vehiculeSelect.options[vehiculeSelect.selectedIndex];
    if (!opt || !opt.value) { alert("Seleccione un vehículo para editar"); return; }

    openModal(`
        <button class="modal-close" onclick="closeModal()">&times;</button>
        <h2>Editar Vehículo</h2>
        <form id="vehiculeForm">
            <input type="hidden" name="id" value="${opt.value}">
            <div class="mb-2">
                <label class="form-label">Matrícula</label>
                <input type="text" class="form-control" name="matriculate" value="${opt.dataset.matriculate}" required>
            </div>
            <div class="d-flex justify-content-end gap-2 mt-3">
                <button type="button" class="btn btn-secondary" onclick="closeModal()">Cancelar</button>
                <button type="submit" class="btn btn-primary">Actualizar</button>
            </div>
        </form>
    `);
    document.getElementById("vehiculeForm").addEventListener("submit", async (e) => {
        e.preventDefault();
        const fd = new FormData(e.target);
        try {
            await apiRequest("editVehicule", { id: fd.get("id"), matriculate: fd.get("matriculate") });
            location.reload();
        } catch (err) { showError(err.message); }
    });
}

// ---------- VEHICULO: eliminar ----------
function openRemoveVehiculeModal() {
    const opt = vehiculeSelect.options[vehiculeSelect.selectedIndex];
    if (!opt || !opt.value) { alert("Seleccione un vehículo para eliminar"); return; }

    openModal(`
        <button class="modal-close" onclick="closeModal()">&times;</button>
        <h2>Eliminar Vehículo</h2>
        <p>¿Está seguro que desea eliminar el vehículo <strong>${opt.dataset.matriculate}</strong>?</p>
        <form id="vehiculeForm">
            <input type="hidden" name="id" value="${opt.value}">
            <div class="d-flex justify-content-end gap-2 mt-3">
                <button type="button" class="btn btn-secondary" onclick="closeModal()">Cancelar</button>
                <button type="submit" class="btn btn-danger">Eliminar</button>
            </div>
        </form>
    `);
    document.getElementById("vehiculeForm").addEventListener("submit", async (e) => {
        e.preventDefault();
        const fd = new FormData(e.target);
        try {
            await apiRequest("removeVehicule", { id: fd.get("id") });
            location.reload();
        } catch (err) { showError(err.message); }
    });
}

// ============ LISTENERS ============
document.getElementById("addDriver").addEventListener("click", openAddDriverModal);
document.getElementById("editDriver").addEventListener("click", openEditDriverModal);
document.getElementById("removeDriver").addEventListener("click", openRemoveDriverModal);

document.getElementById("addVehicule").addEventListener("click", openAddVehiculeModal);
document.getElementById("editVehicule").addEventListener("click", openEditVehiculeModal);
document.getElementById("removeVehicule").addEventListener("click", openRemoveVehiculeModal);

// Cerrar al hacer click fuera
modal.addEventListener("click", (e) => { if (e.target === modal) closeModal(); });

// Cerrar con Escape
document.addEventListener("keydown", (e) => { if (e.key === "Escape") closeModal(); });

// Exponer closeModal para los onclick inline
window.closeModal = closeModal;