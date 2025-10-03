const BASE_URL = import.meta.env.VITE_API_BASE || "http://task.test";

async function request(path, options = {}) {
  const url = `${BASE_URL}${path}`;
  const opts = Object.assign(
    {
      headers: { "Content-Type": "application/json" },
    },
    options
  );

  try {
    const res = await fetch(url, opts);
    const text = await res.text();
    let data = null;
    try {
      data = text ? JSON.parse(text) : null;
    } catch (e) {
      data = text;
    }

    if (!res.ok) {
      const err = new Error(
        data && data.message ? data.message : res.statusText
      );
      err.status = res.status;
      err.body = data;
      throw err;
    }

    return data;
  } catch (err) {
    // Re-throwing for caller to handle; attach path for easier debugging
    err.requestUrl = url;
    throw err;
  }
}

export async function fetchTasks() {
  return request("/tasks");
}

export async function createTask(payload) {
  return request("/tasks", { method: "POST", body: JSON.stringify(payload) });
}

export async function updateTask(id, payload) {
  return request(`/tasks/${encodeURIComponent(id)}`, {
    method: "PUT",
    body: JSON.stringify(payload),
  });
}
export async function changeStatusTask(id, payload) {
  return request(`/tasks-change-status/${encodeURIComponent(id)}`, {
    method: "PUT",
    body: JSON.stringify(payload),
  });
}

export async function deleteTask(id) {
  return request(`/tasks/${encodeURIComponent(id)}`, { method: "DELETE" });
}

export default { fetchTasks, createTask, updateTask, deleteTask };
