import { useEffect, useState } from "react";
import AdminLayout from "@/layouts/main";
import ExpedienteDialog from "@/components/expediente/expediente-dialog";

export default function DialogTest() {
  const [dialogOpen, setDialogOpen] = useState(false);
  const [id, setId] = useState("");
  const [expedientes, setExpedientes] = useState<{ id: string }[]>([]);

  useEffect(() => {
    fetch("/api/prototipo/excel/")
      .then((r) => r.json())
      .then((list) => {
        setExpedientes(list);
        if (list.length > 0) setId(list[0].id);
      })
      .catch(() => setExpedientes([]));
  }, []);

  return (
    <AdminLayout>
      <div style={{ maxWidth: 720 }}>
        <h1 className="h3 mb-2">Prueba de dialog</h1>
        <p className="text-muted mb-4">
          Selecciona un expediente para validar el dialog con Bootstrap.
        </p>

        <div className="card">
          <div className="card-body">
            <label className="form-label">Expediente</label>
            <select
              className="form-select mb-3"
              value={id}
              onChange={(e) => setId(e.target.value)}
            >
              <option value="">Selecciona…</option>
              {expedientes.map((e) => (
                <option key={e.id} value={e.id}>
                  {e.id}
                </option>
              ))}
            </select>

            <button
              className="btn btn-primary"
              onClick={() => setDialogOpen(true)}
              disabled={!id}
            >
              Abrir dialog
            </button>
          </div>
        </div>
      </div>

      <ExpedienteDialog
        idExpediente={id}
        open={dialogOpen}
        onClose={() => setDialogOpen(false)}
      />
    </AdminLayout>
  );
}