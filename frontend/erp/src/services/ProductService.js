import api from "./api";

/**
 * Service for product operations
 */
const ProductService = {
    /**
     * Get all products
     * @param {object} params Query parameters
     * @returns {Promise} Promise with products response
     */
    getProducts: async (params = {}) => {
        const response = await api.get("/products", { params });
        return response.data;
    },

    /**
     * Get a product by ID
     * @param {number} id Product ID
     * @returns {Promise} Promise with product response
     */
    getProductById: async (id) => {
        const response = await api.get(`/products/${id}`);
        return response.data;
    },

    /**
     * Create a new product
     * @param {object} productData Product data
     * @returns {Promise} Promise with create product response
     */
    createProduct: async (productData) => {
        const response = await api.post("/products", productData);
        return response.data;
    },

    /**
     * Update a product
     * @param {number} id Product ID
     * @param {object} productData Product data to update
     * @returns {Promise} Promise with update product response
     */
    updateProduct: async (id, productData) => {
        const response = await api.put(`/products/${id}`, productData);
        return response.data;
    },

    /**
     * Delete a product
     * @param {number} id Product ID
     * @returns {Promise} Promise with delete product response
     */
    deleteProduct: async (id) => {
        const response = await api.delete(`/products/${id}`);
        return response.data;
    },

    /**
     * Get BOMs for a specific product
     * @param {number} productId Product ID
     * @returns {Promise} Promise with BOMs response
     */
    getProductBOMs: async (productId) => {
        const response = await api.get(`/products/${productId}/boms`);
        return response.data;
    },
};

export default ProductService;
