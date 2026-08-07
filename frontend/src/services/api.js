import axios from 'axios'

const token = import.meta.env.VITE_API_TOKEN || localStorage.getItem('api_token') || ''

// Resolve API base: use env if set, otherwise use relative /api which goes via Vite proxy
// This fixes ERR_CONNECTION_REFUSED on other PCs: previously hardcoded localhost:8000 failed on LAN
// When browser is at http://192.168.1.10:5173, /api is http://192.168.1.10:5173/api -> proxied to backend
function resolveApiBase() {
  const env = import.meta.env.VITE_API_URL
  if (env && env.trim() !== '') return env.trim()
  // Default to relative proxy for dev (works for localhost and LAN)
  return '/api'
}

const api = axios.create({
  baseURL: resolveApiBase(),
  headers: {
    'Content-Type': 'application/json',
    'Accept': 'application/json',
    ...(token ? { 'Authorization': `Bearer ${token}` } : {})
  }
})

// Allow dynamic token setup for prod
api.interceptors.request.use(config => {
  const t = import.meta.env.VITE_API_TOKEN || localStorage.getItem('api_token')
  if (t) {
    config.headers.Authorization = `Bearer ${t}`
  }
  return config
})

api.interceptors.response.use(
  r => r,
  err => {
    if (err.response?.status === 401) {
      console.warn('API unauthorized – check VITE_API_TOKEN / API_TOKEN')
    }
    return Promise.reject(err)
  }
)

export default api

// Products = data_barang
export const productService = {
  getAll(params = {}) { return api.get('/products', { params }) },
  getOne(id) { return api.get(`/products/${id}`) },
  create(data) { return api.post('/products', data) },
  update(id, data) { return api.put(`/products/${id}`, data) },
  delete(id) { return api.delete(`/products/${id}`) },
  adjustStock(id, data) { return api.post(`/products/${id}/stock`, data) },
  uploadPhoto(id, file) {
    const fd = new FormData()
    fd.append('photo', file)
    return api.post(`/products/${id}/photo`, fd, { headers: { 'Content-Type': 'multipart/form-data' } })
  },
  deletePhoto(id) { return api.delete(`/products/${id}/photo`) },
  getStats() { return api.get('/products/stats') },
  getLowStock() { return api.get('/products/low-stock') },
  getCategoriesStats() { return api.get('/products/stats/categories') },
  getBoxesStats() { return api.get('/products/stats/boxes') },
  getLogs(id=null) { return api.get('/logs', { params: { id } }) },
  exportCsv(params={}) { return api.get('/export/csv', { params, responseType: 'blob' }) },
  importCsv(file) { 
    const fd = new FormData()
    fd.append('file', file)
    return api.post('/import/csv', fd, { headers: { 'Content-Type': 'multipart/form-data' } }) 
  }
}

export const getPhotoUrl = (foto) => {
  if (!foto) return null
  if (foto.startsWith('http')) return foto
  const clean = foto.replace(/^\//, '')

  // If foto already has uploads/ prefix, keep it
  const env = import.meta.env.VITE_API_URL || ''
  // If env is absolute http, use its host
  if (env && env.startsWith('http')) {
    const base = env.replace(/\/api\/?$/, '')
    return `${base}/${clean}`
  }
  // Otherwise we are using proxy mode (/api). For images, use relative /uploads via proxy
  // Vite proxies /uploads to backend, so /uploads/xyz works for LAN
  // This avoids needing to know backend port/host
  if (clean.startsWith('uploads/')) {
    return `/${clean}`
  }
  // Fallback: absolute via same hostname but backend port 8000 (for direct backend access)
  const protocol = window.location.protocol
  const hostname = window.location.hostname
  return `${protocol}//${hostname}:8000/${clean}`
}

export const categoryService = {
  getAll() { return api.get('/categories') },
  getStats() { return api.get('/categories/stats') },
  getOne(id) { return api.get(`/categories/${encodeURIComponent(id)}`) },
  create(data) { return api.post('/categories', data) },
  update(id, data) { return api.put(`/categories/${encodeURIComponent(id)}`, data) },
  delete(id) { return api.delete(`/categories/${encodeURIComponent(id)}`) }
}

export const locationService = {
  getAll(type='full') { return api.get('/locations', { params: { type } }) },
  getBoxes() { return api.get('/locations/boxes') },
  getLaciByBox(box) { 
    const b = encodeURIComponent(String(box).trim())
    return api.get(`/locations/box/${b}`) 
  },
  getProducts(box, laci=null) { 
    const b = encodeURIComponent(String(box).trim())
    if (laci !== null && laci !== undefined && String(laci).trim() !== '') {
      const l = encodeURIComponent(String(laci).trim())
      return api.get(`/locations/box/${b}/laci/${l}`)
    }
    return api.get(`/locations/box/${b}`)
  },
  getOne(id) { return api.get(`/locations/${encodeURIComponent(id)}`) },
  create(data) { return api.post('/locations', data) },
  update(id, data) { return api.put(`/locations/${encodeURIComponent(id)}`, data) },
  delete(id) { return api.delete(`/locations/${encodeURIComponent(id)}`) }
}
