import axios from 'axios'

const api = axios.create({
  baseURL: import.meta.env.VITE_API_URL || 'http://localhost:8000/api',
  headers: {
    'Content-Type': 'application/json',
    'Accept': 'application/json'
  }
})

export default api

// Products = data_barang
export const productService = {
  getAll(params = {}) { return api.get('/products', { params }) },
  getOne(id) { return api.get(`/products/${id}`) },
  create(data) { return api.post('/products', data) },
  update(id, data) { return api.put(`/products/${id}`, data) },
  delete(id) { return api.delete(`/products/${id}`) },
  adjustStock(id, data) { return api.post(`/products/${id}/stock`, data) },
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

export const categoryService = {
  getAll() { return api.get('/categories') },
  getStats() { return api.get('/categories/stats') },
  create(data) { return api.post('/categories', data) },
  update(id, data) { return api.put(`/categories/${id}`, data) },
  delete(id) { return api.delete(`/categories/${id}`) }
}

export const locationService = {
  getAll(type='full') { return api.get('/locations', { params: { type } }) },
  getBoxes() { return api.get('/locations/boxes') },
  getLaciByBox(box) { return api.get(`/locations/box/${box}`) },
  getProducts(box, laci=null) { 
    if (laci) return api.get(`/locations/box/${box}/laci/${laci}`)
    return api.get(`/locations/box/${box}`)
  },
  getOne(id) { return api.get(`/locations/${id}`) },
  create(data) { return api.post('/locations', data) },
  update(id, data) { return api.put(`/locations/${id}`, data) },
  delete(id) { return api.delete(`/locations/${id}`) }
}
