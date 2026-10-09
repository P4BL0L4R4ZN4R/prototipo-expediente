import Swal from "sweetalert2";

type ConfirmarOpts = {
  titulo: string;
  texto?: string;
  textoConfirmar?: string;
  textoCancelar?: string;
  colorConfirmar?: string;
};

type ToastOpts = {
  mensaje: string;
  duracion?: number;
};

// ─── Confirmar ───────────────────────────────────────────

export const confirmar = ({
  titulo,
  texto = "",
  textoConfirmar = "Confirmar",
  textoCancelar = "Cancelar",
  colorConfirmar = "#3085d6",
}: ConfirmarOpts) =>
  Swal.fire({
    title: titulo,
    text: texto,
    icon: "warning",
    showCancelButton: true,
    confirmButtonColor: colorConfirmar,
    cancelButtonColor: "#6c757d",
    confirmButtonText: textoConfirmar,
    cancelButtonText: textoCancelar,
  });

// ─── Toasts ──────────────────────────────────────────────

export const exito = ({ mensaje, duracion = 2000 }: ToastOpts) =>
  Swal.fire({
    toast: true,
    position: "top-end",
    icon: "success",
    title: mensaje,
    showConfirmButton: false,
    timer: duracion,
    timerProgressBar: true,
  });

export const error = ({ mensaje, duracion = 3000 }: ToastOpts) =>
  Swal.fire({
    toast: true,
    position: "top-end",
    icon: "error",
    title: mensaje,
    showConfirmButton: false,
    timer: duracion,
    timerProgressBar: true,
  });

export const info = ({ mensaje, duracion = 2500 }: ToastOpts) =>
  Swal.fire({
    toast: true,
    position: "top-end",
    icon: "info",
    title: mensaje,
    showConfirmButton: false,
    timer: duracion,
    timerProgressBar: true,
  });