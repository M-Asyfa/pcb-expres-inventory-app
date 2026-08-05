import { createRouter, createWebHistory } from 'vue-router'
import Dashboard from '../views/Dashboard.vue'
import Products from '../views/Products.vue'
import Categories from '../views/Categories.vue'
import Locations from '../views/Locations.vue'

const routes = [
  { path: '/', name: 'Dashboard', component: Dashboard },
  { path: '/products', name: 'Products', component: Products },
  { path: '/categories', name: 'Categories', component: Categories },
  { path: '/locations', name: 'Locations', component: Locations }
]

export default createRouter({
  history: createWebHistory(),
  routes
})
