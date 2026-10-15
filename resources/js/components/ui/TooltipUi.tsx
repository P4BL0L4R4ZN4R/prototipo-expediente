interface Props {
  tooltip: string;
  texto: string;
  contenido: React.ReactNode;
  onClick?: () => void;
}

export default function TooltipUI({
  tooltip,
  texto,
  contenido,
  onClick,
}: Props) {
  return (
    <div className="tooltip-container" onClick={onClick}>
      <span className="tooltip">{tooltip}</span>
      <span className="text">{texto}</span>
      <span>{contenido}</span>
    </div>
  );
}