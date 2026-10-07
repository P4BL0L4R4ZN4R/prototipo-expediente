export type ContextoGlobal = {
  empresa_factura: string;
  cliente: string;
  servicio: string;
  objetivo: string;
  fecha_autorizado: string;
};

export type DatosDuros = {
  fecha_inicio: string | null;
  fecha_termino: string | null;
  costo_p_mes: number | null;
};

export type Concepto = {
  orden: number;
  concepto: string;
  concepto_completo: string;
  proceso: string;
  clave: string;
  descripcion: string;
  costo_p_mes: number | null;
};



export type Calculos = {
  total_programa: number;
  total_por_concepto: Record<string, number>;
  fecha_legal: string | null;
};



export type IaData = {
  empresa_factura: string;
  cliente: string;
  servicio: string;
  objetivo: string;
  conceptos: {
    orden: number;
    concepto: string;
    proceso: string;
  }[];
};

export type Factura = {
  concepto_orden: number;
  total_factura: number;
  no_factura: string;
  folio_fiscal: string;
  fecha: string;
  observaciones: string;
};

export type DataDura = {
  fecha_inicio: string | null;
  fecha_termino: string | null;
  costo_p_mes: number | null;
  conceptos: {
    orden: number;
    clave: string;
    descripcion: string;
    costo_p_mes: number | null;
    facturas: Factura[];
    total: number;
  }[];
  calculos: {
    total_programa: number;
    fecha_legal: string | null;
  };
};

export type Expediente = {
  id: string;
  ia: IaData;
  data: DataDura;
};