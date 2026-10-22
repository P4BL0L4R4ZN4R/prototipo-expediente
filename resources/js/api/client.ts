import axios from 'axios';

const api = axios.create({
  baseURL: '/api',
  headers: {
    Accept: 'application/json',
  },
});

const TOKEN_KEY = 'api_token';
const PLAZA_KEY = 'plaza';

export function getToken(): string | null {
  return localStorage.getItem(TOKEN_KEY);
}

export function getPlaza(): string | null {
  return localStorage.getItem(PLAZA_KEY);
}

export function setAuth(token: string, plaza: string) {
  localStorage.setItem(TOKEN_KEY, token);
  localStorage.setItem(PLAZA_KEY, plaza);
  
}

export function clearAuth() {
  localStorage.removeItem(TOKEN_KEY);
  localStorage.removeItem(PLAZA_KEY);
  
}

api.interceptors.request.use((config) => {
  const token = getToken();
  const plaza = getPlaza();

  
  if (token) config.headers.set('Authorization', `Bearer ${token}`);
  if (plaza) config.headers.set('X-Plaza', plaza);

  // ─────────────────────────────────────────────────────────────
  // CLAVE: FormData necesita multipart/form-data + boundary.
  // Si dejamos 'application/json', axios serializa el FormData a
  // JSON ({"excel":{}}) y Laravel responde 422 "excel required".
  // ─────────────────────────────────────────────────────────────
  if (config.data instanceof FormData) {
    config.headers.delete('Content-Type');
  } else {
    config.headers.set('Content-Type', 'application/json');
  }

  // Log del header final (sin mostrar el token completo)
  const authHeader = config.headers.get('Authorization');
  
  return config;
});

api.interceptors.response.use(
  (response) => {
  
    return response;
  },
  (error) => {
  
    return Promise.reject(error);
  }
);

export async function ensureToken(): Promise<void> {
  if (getToken()) return;
  if (!import.meta.env.DEV) return;

  const plaza = getPlaza() ?? 'test00';
  try {
    const { data } = await api.post('/_dev/token', { plaza });
    setAuth(data.token, data.plaza);
  } catch (err) {
    console.error('[auth] no se pudo acceder:', err);
  }
}

export default api;