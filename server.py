#!/usr/bin/python

import cgi
import glob
import os
import sys
import json 
import ssl
import shutil

from http.server import BaseHTTPRequestHandler,HTTPServer

PortNumber = 8090;
u = "user";
p = "password";

class handler(BaseHTTPRequestHandler):
    
    def do_GET(self):
        self.send_response(200)
        self.send_header("Content-Type","text/html")
        self.send_header("Access-Control-Allow-Origin","*")
        self.end_headers()
        html = "Ready too go!"
        
        self.wfile.write( bytes( html, "utf-8" ) )
            
        return
   
    def do_POST(self):
        self.send_response(200)
        self.send_header("Content-Type","text/html")
        self.send_header("Access-Control-Allow-Origin","*")

        self.end_headers()
        
        
        ctype, pdict = cgi.parse_header(self.headers["content-type"])
        pdict["boundary"] = pdict["boundary"].encode("utf-8")
            
        
        if ctype == "multipart/form-data":
            postvars = cgi.parse_multipart(self.rfile, pdict)
    
        
        #strs = self.rfile.read(int(self.headers["Content-Length"]))
        
        try: url = postvars.get("url")[0].decode()
        except: url = ""
        
        try: server = postvars.get("server")[0].decode()
        except: server = ""
        
        try: url = postvars.get("url")[0].decode()
        except: url = ""
        
        try: func = postvars.get("func")[0].decode()
        except: func = ""
        
        try: path = postvars.get("path")[0].decode()
        except: path = ""
        
        try: type_ = postvars.get("type")[0].decode()
        except: type_ = ""
        
        try: content = postvars.get("content")[0].decode()
        except: content = ""
        
        try: new_path = postvars.get("new_path")[0].decode()
        except: new_path = ""
        
        try: user = postvars.get("user")[0].decode()
        except: user = ""
        
        try: password = postvars.get("password")[0].decode()
        except: password = "" 
        
        try: up = postvars.get("file")[0]
        except: up = ""
        
        html = ""
        
        if u == user and p == password :
            if func == "load":
                path_ = path.replace("*","")
                if os.path.isdir(path_):
                    html =  load( path, url, server, user, password )
                else:
                    listObject = [];
                    listObject.append("file");
                    
                    mode = os.path.splitext(path)[1][1:]
                    
                    if mode == "phtml": mode = "php";
                    if mode == "py": mode = "python";
                    
                    file = open( path, "r" )
                    listObject.append({
                        "content" : file.read(),
                        "ext" : mode,
                        "name" : os.path.basename(path),
                        "path" : path,
                        "url" : url,
                        "server" : server,
                        "user" : user,
                        "password" : password
                    })
                    
                    html = json.dumps(listObject)
                    
            elif func == "save":
                file = open( path, "w" )
                file.write( content )
                file.close()
                
                listObject = [];
                listObject.append("save");
                listObject.append("Save done");
            
                html = json.dumps(listObject)
            
            elif func == "upload":
                listObject = [];
                listObject.append("upload")
                
                fout = open( "./im0.png", "wb" )
                fout.write( up )
                fout.close()
                
                listObject.append("Done");
            
                html = json.dumps(listObject)
                
            elif func == "remove":
                if os.path.isdir(path):
                    shutil.rmtree( path )
                else:
                    os.remove( path )
                    
                listObject = [];
                listObject.append("remove");
                listObject.append("Remove Done");
                listObject.append(url);
                listObject.append(path);
            
                html = json.dumps(listObject)
                
            elif func == "rename":
                listObject = [];
                listObject.append("rename");
                
                os.rename( path, new_path );
                listObject.append("Rename Done");
                
                listObject.append(url);
                listObject.append(path);
                
                listObject.append(new_path);
                
                name = os.path.basename(new_path);
                listObject.append(name);
                
                md = os.path.splitext(new_path)[1][1:]
                listObject.append(md);

                html = json.dumps(listObject)
                
            elif func == "new_file":
                
                listObject = [];
                listObject.append("new_file");
                
                if os.path.exists(path):
                    listObject.append("File Exist");
                else:
                    f = open(path, "w+")
                    listObject.append("File Created");
                    
                html = json.dumps(listObject)
                
            elif func == "new_folder":
                listObject = [];
                listObject.append("new_folder");
                
                if os.path.exists(path):
                    listObject.append("Folder Exist");
                else:
                    os.mkdir(path)
                    listObject.append("Folder Created");
                    
                html = json.dumps(listObject)
                
        else: 
            listObject = [];
            listObject.append("lastElse");
            html = json.dumps(listObject)
            
        
        if sys.version_info[0] == 2:
            self.wfile.write( html )
        else:
            self.wfile.write( bytes( html, "utf-8" ) )
            
        return

def load(path, url, server, user, password):
    listDir = [];
    listFile = [];
    listObject = [];
    listObject.append("folder");
    paths = glob.glob(path)
    
    for p in paths : 
        mode = os.path.splitext(p)[1][1:]
        if os.path.isdir(p):
            listDir.append({
                "mode" : "folder",
                "path" : p+"/*",
                "icon" : "folder",
                "function" : "folder",
                "name" : os.path.basename(p),
                "url" : url,
                "server" : server,
                "user" : user,
                "password" : password
                })
        else:
            listFile.append({
                "mode" : mode,
                "path" : p,
                "icon" : mode+"-file",
                "function" : "file",
                "name" : os.path.basename(p),
                "url" : url,
                "server" : server,
                "user" : user,
                "password" : password
                })
    listDir.extend(listFile)
    listObject.append(listDir)
    
    up = path.replace("*", "");

    listObject.append(os.path.dirname(up)+"/*")
    listObject.append(up)

    #os.path.splitext(path)
    return json.dumps(listObject)


try:
    server = HTTPServer(( "", PortNumber),handler)
    server.serve_forever()

except KeyboardInterrupt:
    server.socket.close()
