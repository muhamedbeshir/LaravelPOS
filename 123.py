import subprocess
import webbrowser
import socket
import time
import re
import threading
import sys
import atexit
import winreg
import tkinter as tk
from tkinter import filedialog, messagebox, ttk
import os
import queue

# --- Configuration ---
REG_PATH = r"Software\BulldozerPOS"
REG_KEY_NAME = "ProjectPath"
APP_NAME = "🚀 نظام كاشير بولدوزر"
# --- End Configuration ---

server_process = None

def cleanup():
    global server_process
    if server_process:
        print("\nإيقاف الخادم...")
        server_process.terminate()
        try:
            server_process.wait(timeout=5)
        except subprocess.TimeoutExpired:
            server_process.kill()
        print("تم إيقاف الخادم بنجاح.")

atexit.register(cleanup)

def get_project_path_from_registry():
    try:
        with winreg.OpenKey(winreg.HKEY_CURRENT_USER, REG_PATH, 0, winreg.KEY_READ) as key:
            value, _ = winreg.QueryValueEx(key, REG_KEY_NAME)
            return value
    except FileNotFoundError:
        return None

def save_project_path_to_registry(path):
    try:
        with winreg.CreateKey(winreg.HKEY_CURRENT_USER, REG_PATH) as key:
            winreg.SetValueEx(key, REG_KEY_NAME, 0, winreg.REG_SZ, path)
    except Exception as e:
        messagebox.showerror("خطأ", f"فشل في حفظ مسار المشروع: {e}")

def prompt_for_project_path():
    root = tk.Tk()
    root.withdraw()
    while True:
        messagebox.showinfo("مطلوب مسار المشروع", "الرجاء اختيار مجلد مشروع Laravel الخاص بك.")
        path = filedialog.askdirectory(title="اختر مجلد المشروع")
        if not path:
            sys.exit(0)
        if os.path.isfile(os.path.join(path, 'artisan')):
            return path
        else:
            messagebox.showerror("مجلد غير صالح", "المجلد المحدد لا يحتوي على ملف 'artisan'. الرجاء المحاولة مرة أخرى.")

def server_thread_logic(project_path, q):
    global server_process
    try:
        q.put(("status", "1/4: جاري تشغيل خادم PHP..."))
        server_process = subprocess.Popen(
            ['php', 'artisan', 'serve'], cwd=project_path,
            stdout=subprocess.PIPE, stderr=subprocess.PIPE,
            text=True, encoding='utf-8', creationflags=subprocess.CREATE_NO_WINDOW
        )

        q.put(("status", "2/4: جاري البحث عن منفذ التشغيل..."))
        url_pattern = re.compile(r"http://(127\.0\.0\.1|localhost):(\d+)")
        server_url, host, port = None, None, None

        for line in iter(server_process.stdout.readline, ''):
            match = url_pattern.search(line)
            if match:
                server_url, host, port = match.group(0), match.group(1), int(match.group(2))
                break

        if not server_url:
            raise RuntimeError("لم يتم العثور على عنوان الخادم. هل PHP مثبت ومضاف إلى متغيرات النظام؟")

        q.put(("status", "3/4: جاري فحص جاهزية الخادم..."))
        while True:
            try:
                with socket.create_connection((host, port), timeout=0.5):
                    break
            except (socket.timeout, ConnectionRefusedError):
                if server_process.poll() is not None:
                    raise RuntimeError("توقف الخادم بشكل غير متوقع.")
                time.sleep(0.5)

        q.put(("status", "4/4: اكتمل التحميل! سيتم فتح المتصفح الآن..."))
        time.sleep(1.5)
        webbrowser.open(server_url)
        q.put(("done", server_url))
    except Exception as e:
        q.put(("error", str(e)))

class LoadingWindow:
    def __init__(self, master):
        self.master = master
        self.queue = queue.Queue()

        self.master.title("🚀 تشغيل نظام الكاشير - BulldozerPOS")
        self.master.geometry("440x260")
        self.master.resizable(False, False)
        self.master.configure(bg="#1e1e2f")
        self.master.eval('tk::PlaceWindow . center')

        style = ttk.Style(self.master)
        style.theme_use('clam')
        style.configure("Dark.TFrame", background="#1e1e2f")
        style.configure("Dark.TLabel", foreground="#ecf0f1", background="#1e1e2f")
        style.configure("Header.TLabel", font=("Segoe UI Semibold", 20), foreground="#00cec9", background="#1e1e2f")
        style.configure("SubHeader.TLabel", font=("Segoe UI", 10), foreground="#dfe6e9", background="#1e1e2f")
        style.configure("Status.TLabel", font=("Segoe UI", 11, "bold"), foreground="#fab1a0", background="#1e1e2f")
        style.configure("Horizontal.TProgressbar", troughcolor='#2d3436', background='#00b894', thickness=16)

        main_frame = ttk.Frame(self.master, style="Dark.TFrame", padding=25)
        main_frame.pack(fill=tk.BOTH, expand=True)

        header_label = ttk.Label(main_frame, text=APP_NAME, style="Header.TLabel")
        header_label.pack(pady=(5, 2))

        desc_label = ttk.Label(main_frame, text="💼 نظام كاشير متكامل لإدارة المبيعات والمطاعم", style="SubHeader.TLabel")
        desc_label.pack(pady=(0, 15))

        self.status_var = tk.StringVar(value="🔄 جاري البدء...")
        status_display = ttk.Label(main_frame, textvariable=self.status_var, style="Status.TLabel")
        status_display.pack(pady=(5, 10))

        self.progress = ttk.Progressbar(main_frame, style="Horizontal.TProgressbar", mode='determinate', length=360, maximum=4)
        self.progress.pack(pady=5)

        footer_label = ttk.Label(main_frame, text="⏳ سيتم إغلاق هذه النافذة تلقائيًا بعد التشغيل الناجح.", style="SubHeader.TLabel", font=("Segoe UI", 8))
        footer_label.pack(side=tk.BOTTOM, pady=(20, 0))

    def process_queue(self):
        try:
            msg_type, msg_payload = self.queue.get_nowait()
            if msg_type == "status":
                self.status_var.set(msg_payload)
                try:
                    stage = int(msg_payload.split('/')[0])
                    self.progress['value'] = stage
                except:
                    pass
            elif msg_type == "done":
                print(f"تم تشغيل الخادم على الرابط {msg_payload}. اضغط Ctrl+C في نافذة الطرفية للإيقاف.")
                self.master.destroy()
            elif msg_type == "error":
                messagebox.showerror("خطأ في التشغيل", msg_payload)
                self.master.destroy()
        except queue.Empty:
            pass
        finally:
            self.master.after(100, self.process_queue)

def main():
    project_path = get_project_path_from_registry()
    if not project_path or not os.path.isdir(project_path):
        project_path = prompt_for_project_path()
        save_project_path_to_registry(project_path)

    print(f"استخدام مسار المشروع: {project_path}")

    root = tk.Tk()
    app = LoadingWindow(root)

    thread = threading.Thread(target=server_thread_logic, args=(project_path, app.queue))
    thread.daemon = True

    root.after(100, app.process_queue)
    thread.start()

    root.mainloop()

    if server_process:
        try:
            print("النافذة أُغلقت، لكن الخادم لا يزال يعمل. اضغط Ctrl+C لإيقافه.")
            server_process.wait()
        except KeyboardInterrupt:
            print("\nتم استلام Ctrl+C. جاري الخروج.")
            sys.exit(0)

if __name__ == "__main__":
    main()
