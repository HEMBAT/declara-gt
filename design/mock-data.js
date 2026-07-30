// Datos de ejemplo y lógica de cálculo para Declara-GT
// Régimen ISR Opcional Simplificado Mensual (Guatemala)

export const IVA_RATE = 0.12;

export const RATE_HISTORY = {
  iva: [
    { id: 'iva-1', tasa: 12, vigenteDesde: '1992-08-01', vigenteHasta: null, nota: 'Ley del Impuesto al Valor Agregado, Decreto 27-92' }
  ],
  isr: [
    { id: 'isr-1', tramo1Tasa: 5, tramo1Limite: 30000, tramo2Tasa: 7, vigenteDesde: '2013-01-01', vigenteHasta: null, nota: 'Régimen Opcional Simplificado sobre Ingresos, Art. 44 Ley de Actualización Tributaria' }
  ]
};

export function calcISR(baseSinIva) {
  const limite = RATE_HISTORY.isr[0].tramo1Limite;
  const t1Tasa = RATE_HISTORY.isr[0].tramo1Tasa / 100;
  const t2Tasa = RATE_HISTORY.isr[0].tramo2Tasa / 100;
  const base = Math.max(baseSinIva, 0);
  const tramo1Base = Math.min(base, limite);
  const tramo2Base = Math.max(base - limite, 0);
  const tramo1Monto = tramo1Base * t1Tasa;
  const tramo2Monto = tramo2Base * t2Tasa;
  return {
    tramo1Base, tramo1Monto, tramo1Tasa: t1Tasa,
    tramo2Base, tramo2Monto, tramo2Tasa: t2Tasa,
    total: tramo1Monto + tramo2Monto
  };
}

export function formatQ(n, opts) {
  const withSign = opts && opts.sign;
  const neg = n < 0;
  const abs = Math.abs(n);
  const str = abs.toLocaleString('es-GT', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
  const sign = neg ? '-' : (withSign ? '+' : '');
  return sign + 'Q' + str;
}

export function formatFecha(iso) {
  const meses = ['ene', 'feb', 'mar', 'abr', 'may', 'jun', 'jul', 'ago', 'sep', 'oct', 'nov', 'dic'];
  const [y, m, d] = iso.split('-').map(Number);
  return `${String(d).padStart(2, '0')} ${meses[m - 1]} ${y}`;
}

export const MESES = ['enero', 'febrero', 'marzo', 'abril', 'mayo', 'junio', 'julio', 'agosto', 'septiembre', 'octubre', 'noviembre', 'diciembre'];

export function periodLabel(id) {
  const [y, m] = id.split('-').map(Number);
  const mes = MESES[m - 1];
  return mes.charAt(0).toUpperCase() + mes.slice(1) + ' ' + y;
}

export const CLIENTS = [
  { nit: '5891234-5', nombre: 'Distribuidora San Rafael, S.A.', tipo: 'bien' },
  { nit: '7712340-1', nombre: 'Constructora Los Cipreses, S.A.', tipo: 'servicio' },
  { nit: '4456781-2', nombre: 'Ferretería El Progreso', tipo: 'bien' },
  { nit: '8890123-K', nombre: 'Transportes Quetzal, S.A.', tipo: 'combustible' },
  { nit: '6623450-9', nombre: 'Consultoría Ixchel', tipo: 'servicio' },
  { nit: '3345678-0', nombre: 'Comercial La Bendición', tipo: 'bien' },
  { nit: '9987654-3', nombre: 'Servicios Profesionales Monteverde', tipo: 'servicio' },
  { nit: '2211987-6', nombre: 'Estación de Servicio Copán', tipo: 'combustible' },
  { nit: 'CF', nombre: 'Consumidor Final', tipo: 'bien' }
];

function inv(id, fecha, nit, nombre, tipoDte, tipo, base, opts) {
  opts = opts || {};
  const monto = +(base * (1 + IVA_RATE)).toFixed(2);
  return {
    id, fecha, clienteNit: nit, clienteNombre: nombre,
    tipoDte, tipo, monto, anulada: !!opts.anulada
  };
}

export const PERIODS = [
  {
    id: '2026-03', status: 'pendiente', fechaPresentacion: null,
    invoices: [],
    retenciones: []
  },
  {
    id: '2026-04', status: 'calculado', fechaPresentacion: null,
    invoices: [
      inv('f-2604-1', '2026-04-02', '7712340-1', 'Constructora Los Cipreses, S.A.', 'FCAM', 'servicio', 12000),
      inv('f-2604-2', '2026-04-05', '5891234-5', 'Distribuidora San Rafael, S.A.', 'FACT', 'bien', 9000),
      inv('f-2604-3', '2026-04-09', '3345678-0', 'Comercial La Bendición', 'FACT', 'bien', 4000),
      inv('f-2604-4', '2026-04-13', '9987654-3', 'Servicios Profesionales Monteverde', 'FCAM', 'servicio', 10000),
      inv('f-2604-5', '2026-04-17', '2211987-6', 'Estación de Servicio Copán', 'FACT', 'combustible', 6000),
      inv('f-2604-6', '2026-04-20', '4456781-2', 'Ferretería El Progreso', 'NCRE', 'bien', -2000),
      inv('f-2604-7', '2026-04-24', 'CF', 'Consumidor Final', 'FACT', 'bien', 6000, { anulada: true })
    ],
    retenciones: []
  },
  {
    id: '2026-05', status: 'declarado', fechaPresentacion: '2026-06-04',
    invoices: [
      inv('f-2605-1', '2026-05-03', '5891234-5', 'Distribuidora San Rafael, S.A.', 'FACT', 'bien', 8000),
      inv('f-2605-2', '2026-05-07', '6623450-9', 'Consultoría Ixchel', 'FCAM', 'servicio', 6000),
      inv('f-2605-3', '2026-05-12', '4456781-2', 'Ferretería El Progreso', 'FACT', 'bien', 5000),
      inv('f-2605-4', '2026-05-18', 'CF', 'Consumidor Final', 'FACT', 'bien', 2000),
      inv('f-2605-5', '2026-05-21', '8890123-K', 'Transportes Quetzal, S.A.', 'FACT', 'combustible', 7000)
    ],
    retenciones: [
      { nit: '8890123-K', cliente: 'Transportes Quetzal, S.A.', monto: 200, fecha: '2026-05-21' }
    ]
  },
  {
    id: '2026-06', status: 'declarado', fechaPresentacion: '2026-07-05',
    invoices: [
      inv('f-2606-1', '2026-06-02', '5891234-5', 'Distribuidora San Rafael, S.A.', 'FACT', 'bien', 6000),
      inv('f-2606-2', '2026-06-04', '7712340-1', 'Constructora Los Cipreses, S.A.', 'FCAM', 'servicio', 15000),
      inv('f-2606-3', '2026-06-06', '4456781-2', 'Ferretería El Progreso', 'FACT', 'bien', 4000),
      inv('f-2606-4', '2026-06-09', '8890123-K', 'Transportes Quetzal, S.A.', 'FACT', 'combustible', 9000),
      inv('f-2606-5', '2026-06-11', '6623450-9', 'Consultoría Ixchel', 'FCAM', 'servicio', 7000),
      inv('f-2606-6', '2026-06-14', '3345678-0', 'Comercial La Bendición', 'FACT', 'bien', 3000),
      inv('f-2606-7', '2026-06-16', 'CF', 'Consumidor Final', 'FACT', 'bien', 1500),
      inv('f-2606-8', '2026-06-19', '9987654-3', 'Servicios Profesionales Monteverde', 'FCAM', 'servicio', 8000),
      inv('f-2606-9', '2026-06-22', '2211987-6', 'Estación de Servicio Copán', 'FACT', 'combustible', 2000),
      inv('f-2606-10', '2026-06-25', '4456781-2', 'Ferretería El Progreso', 'NCRE', 'bien', -1000),
      inv('f-2606-11', '2026-06-27', '5891234-5', 'Distribuidora San Rafael, S.A.', 'FACT', 'bien', 3000, { anulada: true })
    ],
    retenciones: [
      { nit: '7712340-1', cliente: 'Constructora Los Cipreses, S.A.', monto: 450, fecha: '2026-06-04' },
      { nit: '8890123-K', cliente: 'Transportes Quetzal, S.A.', monto: 250, fecha: '2026-06-09' }
    ]
  },
  {
    id: '2026-07', status: 'pendiente', fechaPresentacion: null,
    invoices: [
      inv('f-2607-1', '2026-07-01', '5891234-5', 'Distribuidora San Rafael, S.A.', 'FACT', 'bien', 5000),
      inv('f-2607-2', '2026-07-03', '7712340-1', 'Constructora Los Cipreses, S.A.', 'FCAM', 'servicio', 11000),
      inv('f-2607-3', '2026-07-05', '4456781-2', 'Ferretería El Progreso', 'FACT', 'bien', 3000),
      inv('f-2607-4', '2026-07-08', '8890123-K', 'Transportes Quetzal, S.A.', 'FACT', 'combustible', 8000),
      inv('f-2607-5', '2026-07-10', '6623450-9', 'Consultoría Ixchel', 'FCAM', 'servicio', 6000),
      inv('f-2607-6', '2026-07-12', 'CF', 'Consumidor Final', 'FACT', 'bien', 1000),
      inv('f-2607-7', '2026-07-15', '3345678-0', 'Comercial La Bendición', 'NCRE', 'bien', -500),
      inv('f-2607-8', '2026-07-18', '9987654-3', 'Servicios Profesionales Monteverde', 'FCAM', 'servicio', 4000, { anulada: true })
    ],
    retenciones: [
      { nit: '7712340-1', cliente: 'Constructora Los Cipreses, S.A.', monto: 150, fecha: '2026-07-03' },
      { nit: '8890123-K', cliente: 'Transportes Quetzal, S.A.', monto: 100, fecha: '2026-07-08' }
    ]
  }
];

// Lote simulado que aparece al "soltar" cualquier archivo en la pantalla de Importación.
export const IMPORT_BATCH = {
  periodId: '2026-08',
  invoices: [
    inv('f-2608-1', '2026-08-02', '5891234-5', 'Distribuidora San Rafael, S.A.', 'FACT', 'bien', 4000),
    inv('f-2608-2', '2026-08-04', '7712340-1', 'Constructora Los Cipreses, S.A.', 'FCAM', 'servicio', 9000),
    inv('f-2608-3', '2026-08-06', '1123456-7', 'Repuestos Nahualá, S.A.', 'FACT', null, 3000),
    inv('f-2608-4', '2026-08-09', '8890123-K', 'Transportes Quetzal, S.A.', 'FACT', 'combustible', 5000),
    inv('f-2608-5', '2026-08-12', '6654321-8', 'Bufete Asociado Sac-Bé', 'FCAM', null, 7000),
    inv('f-2608-6', '2026-08-15', 'CF', 'Consumidor Final', 'FACT', 'bien', 1200)
  ],
  newClients: [
    { nit: '1123456-7', nombre: 'Repuestos Nahualá, S.A.' },
    { nit: '6654321-8', nombre: 'Bufete Asociado Sac-Bé' }
  ]
};

export function computeTotals(period) {
  const activas = period.invoices.filter(i => !i.anulada);
  const totalFacturado = +activas.reduce((s, i) => s + i.monto, 0).toFixed(2);
  const baseSinIva = +(totalFacturado / (1 + IVA_RATE)).toFixed(2);
  const totalIva = +(totalFacturado - baseSinIva).toFixed(2);
  const isr = calcISR(baseSinIva);
  const retenciones = +period.retenciones.reduce((s, r) => s + r.monto, 0).toFixed(2);
  const montoFinal = +(isr.total - retenciones).toFixed(2);
  return { totalFacturado, baseSinIva, totalIva, isr, retenciones, montoFinal, cantidadFacturas: activas.length };
}
