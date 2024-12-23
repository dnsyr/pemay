CREATE TABLE Penjualan (
    ID VARCHAR2(10) DEFAULT 'PJ' || LPAD(Penjualan_Seq.NEXTVAL, 8, '0'),
    PEGAWAI_ID VARCHAR2(10),
    PEMILIKHEWAN_ID VARCHAR2(10),
    TANGGALTRANSAKSI TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRODUK SYS.ODCINUMBERLIST,
    TOTAL_BIAYA NUMBER(10,2),
    onDelete NUMBER(1) DEFAULT 0,
    CONSTRAINT PK_Penjualan PRIMARY KEY (ID),
    CONSTRAINT FK_Penjualan_Pegawai FOREIGN KEY (PEGAWAI_ID) REFERENCES Pegawai(ID),
    CONSTRAINT FK_Penjualan_PemilikHewan FOREIGN KEY (PEMILIKHEWAN_ID) REFERENCES PemilikHewan(ID)
); 