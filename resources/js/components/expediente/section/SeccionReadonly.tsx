interface Props {
  titulo: string;
  texto: string | null | undefined;
}

export default function SeccionReadonly({ titulo, texto }: Props) {
  return (
    <div className="card mb-3">
      <div className="card-header fw-bold text-capitalize">{titulo}</div>
      <div className="card-body">
        <div
          className="text-muted"
          style={{ whiteSpace: "pre-wrap", fontSize: 14 }}
        >
          {texto || <em>Sin texto</em>}
        </div>
      </div>
    </div>
  );
}