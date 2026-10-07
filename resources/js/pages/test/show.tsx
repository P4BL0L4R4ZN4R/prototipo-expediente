import { usePage } from "@inertiajs/react";
import AdminLayout from "@/layouts/main";
import TablaConceptos from "./ci";   

export default function Show() {
  const { id } = usePage<{ id: string }>().props;

  return (
    <AdminLayout>
      <div style={{ maxWidth: 900 }}>
        <h1 style={{ fontSize: 22, fontWeight: 600, marginBottom: 6 }}>
          Expediente {id}
        </h1>
    

        <TablaConceptos idExpediente={id} />
      </div>
    </AdminLayout>
  );
}